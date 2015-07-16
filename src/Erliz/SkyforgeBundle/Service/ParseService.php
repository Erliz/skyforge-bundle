<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   15.01.2015
 */

namespace Erliz\SkyforgeBundle\Service;


use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client as Client;
use Erliz\SkyforgeBundle\Extension\Client\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Post\PostBodyInterface;
use GuzzleHttp\Subscriber\Cookie;
use RuntimeException;

class ParseService
{
    /** @var Client */
    private $client;
    /** @var CookieJar */
    private $cookieJar;
    /** @var string */
    private $cookieFile = '/home/sites/erliz.ru/app/cache/curl/cookie_%s.json';
    /** @var array */
    private $config;
    /** @var array */
    private $authData;
    /** @var bool */
    private $tryAuthorize = false;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $data
     */
    public function setAuthData($data)
    {
        $this->authData = $data;
    }

    /**
     * @return array
     */
    private function getAuthData()
    {
        if (!$this->authData) {
            throw new RuntimeException('No auth data for parser');
        }

        return $this->authData;
    }

    /**
     * @return string
     */
    private function getCookieFile()
    {
        return sprintf($this->cookieFile, $this->getAuthData()['login']);
    }

    /**
     * @return array
     */
    private function createHeaders()
    {
        return array(
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.130 Safari/537.36',
            'Accept'          => 'text/javascript, text/html, application/xml, text/xml, */*',
            'Accept-Language' => 'ru,en-US;q=0.8,en;q=0.6',
            'Accept-Encoding' => 'gzip, deflate',
            'Cache-Control'   => 'no-cache',
            'Host'            => 'portal.sf.mail.ru',
            'Origin'          => 'https://portal.sf.mail.ru'
        );
    }

    /**
     * @param array  $headers
     * @param string $referer
     */
    private function addReferer(array &$headers, $referer)
    {
        if ($referer) {
            $headers['Referer'] = $referer;
        }
    }

    /**
     * @param string $url
     * @param array  $headers
     */
    private function addAjaxFlags(&$url, array &$headers)
    {
        $headers['X-Requested-With'] = 'XMLHttpRequest';

        $token = $this->cookieJar->getCookieValueByName('csrf_token');

        if (!$token) {
            $this->authorize();
            $token = $this->cookieJar->getCookieValueByName('csrf_token');
        }

        if (strpos($url, '?') === false) {
            $glue = '?';
        } else {
            $glue = '&';
        }
        $url .= sprintf($glue . 'csrf_token=%s', $token);
    }

    /**
     * @param string $url
     * @param string $body
     * @param bool   $isAjax
     *
     * @return string
     */
    private function checkResponse($url, $body, $isAjax)
    {
        if ($isAjax) {
            if (json_decode($body) === null) {
                if (!$this->isAuthorized($body)) {
                    if ($this->tryAuthorize) {
                        throw new RuntimeException('Not curl authorized', 403);
                    }
                    $this->authorize();
                    $body = $this->getPage($url, $isAjax);
                }
            }
        } else {
            if ($this->isServerUpdating($body)) {
                throw new RuntimeException('Portal is updating now');
            } elseif (!$this->isAuthorized($body)) {
                if ($this->tryAuthorize) {
                    throw new RuntimeException('Not curl authorized', 403);
                }
                $this->authorize();
                $body = $this->getPage($url, $isAjax);
            }
        }

        return $body;
    }

    /**
     * @param string      $url
     * @param bool        $isAjax
     * @param null|string $referer
     * @param array       $post
     *
     * @return string
     */
    public function getPage($url, $isAjax = false, $referer = null, $post = array())
    {
        $this->createClient();

        $headers = $this->createHeaders();
        $this->addReferer($headers, $referer);

        if ($isAjax) {
            $this->addAjaxFlags($url, $headers);
        }

        if ($post) {
            $request = $this->client->createRequest('POST', $url, array('headers' => $headers));

            /** @var PostBodyInterface $requestBody */
            $requestBody = $request->getBody();
            foreach($post as $key => $data) {
                $requestBody->setField($key, $data);
            }
        } else {
            $request = $this->client->createRequest('GET', $url, array('headers' => $headers));
        }

        try {
            $response = $this->client->send($request);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        $this->exportCookie();
        $body = $response->getBody(true);
        $body = $this->checkResponse($url, $body->getContents(), $isAjax);

        return $body;
    }

    public function sendMessage($playerId, $message)
    {
        $url = sprintf('https://portal.sf.mail.ru/skyforgenews.layout.contactio.outgoingmessagefield:sendmessageevent');
        $this->createClient();
        $headers = $this->createHeaders();
        $headers['X-Prototype-Version'] = '1.7';
        $this->addReferer($headers, sprintf('https://portal.sf.mail.ru/wall/%s?page=1', $playerId));
        $this->addAjaxFlags($url, $headers);
        $request = $this->client->createRequest('POST', $url, array('headers' => $headers));

        /** @var PostBodyInterface $requestBody */
        $requestBody = $request->getBody();
        $requestBody->setField('t:zoneid', 'outgoingMessagesZone');
        $requestBody->setField('message', $message);
        $requestBody->setField('toId', $playerId);
        $requestBody->setField('isRoom', 'false');

        try {
//            $this->authorize();
            $response = $this->client->send($request);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        $this->exportCookie();
        $body = $response->getBody(true);

        var_dump((string) $body);
    }

    private function createClient($force = false, $cookieless = false)
    {
        if (!empty($this->client) && !$force) {
            return;
        }

        libxml_use_internal_errors(true);
        $this->cookieJar = new CookieJar();

        if (file_exists($this->getCookieFile()) && !$cookieless) {
            foreach (json_decode(file_get_contents($this->getCookieFile())) as $cookie) {
                $this->cookieJar->setCookie(new SetCookie((array) $cookie));
            }
        }
        $this->client = new Client(array('defaults'=>array('subscribers'=>array(new Cookie($this->cookieJar)))));
    }

    private function exportCookie()
    {
        $exportCookie = array();
        /** @var SetCookie $cookie */
        foreach ($this->cookieJar->toArray() as $cookie) {
            $exportCookie[] = $cookie;
        }

        file_put_contents($this->getCookieFile(), json_encode($exportCookie));
    }

    private function authorize()
    {
        $this->createClient(true, true);

        // get mpop cookie
        $this->client->get(
            sprintf('https://auth.mail.ru/cgi-bin/auth?Login=%s&Password=%s&Page=https://portal.sf.mail.ru/skyforgenews', $this->getAuthData()['login'], $this->getAuthData()['password'])
        );

        $this->client->get('https://portal.sf.mail.ru/skyforgenews');

        $this->exportCookie();

        echo "authorize!\n";
        $this->tryAuthorize = true;
    }

    /**
     * @param $responseBody
     *
     * @return DOMDocument
     */
    public function getPageDom($responseBody)
    {
        $pageDom = new DOMDocument("1.0", "utf-8");
        $pageDom->loadHTML('<?xml encoding="UTF-8">' . $responseBody);

        foreach ($pageDom->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $pageDom->removeChild($item);
            }
        }

        return $pageDom;
    }

    private function getDonationFromPantheonOrderPage($body)
    {
        $dom = $this->getPageDom($body);
        $pageXPath = new DOMXpath($dom);
        $membersDom = $pageXPath->query(
            "//div[@class='is-p-10 table-responsive guild-roster-members']//tr"
        );
        if (!$membersDom->length) {
            throw new RuntimeException('No members found on pantheon page', 50);
        }

        $usersDonationInfo = array();

        foreach ($membersDom as $row) {
            $elementDom = new DOMDocument();
            $elementDom->loadHTML($row->ownerDocument->saveXML($row));

            $infoXPath = new DOMXPath($elementDom);

            $userId = '';
            $userName = '';
            $balance = 0;

            $nameBlock = $infoXPath->query('//div[@class="ubox-title"]/a/@href');
            if ($nameBlock->length) {
                $userId = $this->getPlayerIdFromWallUrl($nameBlock->item(0)->textContent);
            }
            // first row
            if (!$userId) {
                continue;
            }

            $userNick = $infoXPath->query("//div[@class='ubox-title']/a")->item(0)->textContent;

            $nameBlock = $infoXPath->query("//p[@class='ubox-name']/span[1]");
            if($nameBlock->length) {
                $userName = $nameBlock->item(0)->textContent;
            }

            $balanceBlock = $infoXPath->query('//td[4]');
            if ($balanceBlock->length) {
                $balance = (int) $balanceBlock->item(0)->textContent;
            }

            $usersDonationInfo[$userId] = array(
                'name' => $userName,
                'nick' => $userNick,
                'balance' => $balance
            );
        }

        return $usersDonationInfo;
    }

    private function getPageCountFromPantheonOrderPage($body)
    {
        $dom = $this->getPageDom($body);
        $pageXPath = new DOMXpath($dom);
        $pagesCountBlock = $pageXPath->query(
            '(//div[@class="paging"]/a)[last()]'
        );

        if (!$pagesCountBlock->length) {
            return 0;
        }

        return (int) $pagesCountBlock->item(0)->textContent;
    }

    /**
     * @param string $pantheonId
     *
     * @return array
     */
    public function getDonationFromPantheonPage($pantheonId)
    {
        $this->authorize();
        $url = 'https://portal.sf.mail.ru/guild/roster/%s?page=%s';

        $body = $this->getPage(sprintf($url, $pantheonId, 1));
        $pagesCount = $this->getPageCountFromPantheonOrderPage($body);
        if ($pagesCount == 0) {
            throw new \InvalidArgumentException(sprintf('No orders in pantheon with id %s', $pantheonId));
        }

        $usersDonationInfo = $this->getDonationFromPantheonOrderPage($body);
        for ($pageNumber = 2; $pageNumber <= $pagesCount; $pageNumber++) {
            $body = $this->getPage(sprintf($url, $pantheonId, $pageNumber));
            $usersDonationInfo += $this->getDonationFromPantheonOrderPage($body);
            sleep(rand(1, 2));
        }

        return $usersDonationInfo;
    }

    /**
     * @param string $body
     *
     * @return array
     */
    public function getMembersFromCommunityPage($body)
    {
        $dom = $this->getPageDom($body);

        $pageXPath = new DOMXpath($dom);
        $membersDom = $pageXPath->query(
            "//div[@class='card-wrap set-owner card-wrap_s']"
        );
        if (!$membersDom->length) {
            throw new RuntimeException('No members found on community page', 50);
        }

        $storage = array();

        foreach ($membersDom as $menuItemDom) {
            $elementDom = new DOMDocument();
            $elementDom->loadHTML($menuItemDom->ownerDocument->saveXML($menuItemDom));

            $infoXPath = new DOMXPath($elementDom);

            $name = null;
            $nameBlock = $infoXPath->query("//p[@class='ubox-name']/span[1]");
            if($nameBlock->length) {
                 $name = $nameBlock->item(0)->textContent;
            }

            $class = null;
            $classBlock = $infoXPath->query("//p[@class='ubox-name']/span[2]");
            if($classBlock->length) {
                $class = $classBlock->item(0)->textContent;
            }

            $itemDataNodes = array(
                'pic' => $infoXPath->query("//div[@class='upic-img']/img[@class=' b-tip']/@src")->item(0)->textContent,
                'nick' => $infoXPath->query("//div[@class='ubox-title']/a")->item(0)->textContent,
                'name' => $name,
                'class' => $class,
                'wall_url' => $infoXPath->query("//div[@class='ubox-title']/a/@href")->item(0)->textContent
            );
            $itemDataNodes['id'] = $this->getPlayerIdFromWallUrl($itemDataNodes['wall_url']);
            unset($itemDataNodes['wall_url']);

            $itemDataNodes['name'] = preg_replace('/\n\s+\|/', '', $itemDataNodes['name']);
            foreach ($itemDataNodes as $key => $item) {
                $itemDataNodes[$key] = mb_convert_encoding(trim($item), 'cp1252', 'utf-8');
            }
            
            $storage[$itemDataNodes['id']] = (object) $itemDataNodes;
        }

        return $storage;
    }

    /**
     * @param string $wallUrl
     *
     * @return string
     */
    private function getPlayerIdFromWallUrl($wallUrl)
    {
        return preg_replace('/https:\/\/portal\.sf\.mail\.ru\/wall\/([0-9]+)/', '$1', $wallUrl);
    }

    /**
     * @param string $playerId
     *
     * @return mixed
     */
    public function getPlayerItems($playerId)
    {
        $playerStatUrlTemplate = 'https://portal.sf.mail.ru/api/game/item/dress:loadData?uid=%s';
        $playerUrlTemplate = 'https://portal.sf.mail.ru/user/avatar/%s';

        $responseMessage = $this->getPage(
            sprintf($playerStatUrlTemplate, $playerId),
            true,
            sprintf($playerUrlTemplate, $playerId)
        );

        if($responseMessage == '{}') {
            sleep(1);
            try {
                $this->getPage(
                    sprintf($playerUrlTemplate, $playerId)
                );
            } catch (RuntimeException $e) {
                if ($e->getCode() == 403) {
                   return json_decode('{}');
                }
                throw $e;
            }
            $responseMessage = $this->getPage(
                sprintf($playerStatUrlTemplate, $playerId),
                true,
                sprintf($playerUrlTemplate, $playerId)
            );
        }

        return json_decode($responseMessage);
    }

    /**
     * @param $playerId
     *
     * @return \stdClass
     */
    public function getPlayerStat($playerId)
    {
        $playerStatUrlTemplate = 'https://portal.sf.mail.ru/api/game/stats/StatsApi:getAvatarStats/%s';
        $responseMessage = $this->getPage(sprintf($playerStatUrlTemplate, $playerId), true);

        return json_decode($responseMessage);
    }

    /**
     * @param int $id
     *
     * @return array|bool
     */
    public function getDataFromProfilePage($id)
    {
        $profileUrlTemplate = 'https://portal.sf.mail.ru/user/avatar/%s';
        $responseMessage = $this->getPage(sprintf($profileUrlTemplate, $id));

        $pageXPath = new DOMXpath($this->getPageDom($responseMessage));
        if (!$pageXPath->query("//div[@class='s-avatar_info']")->item(0)->hasChildNodes()) {
            throw new RuntimeException('Profile have no avatar data', 403);
        }

        $nick = $pageXPath->query("//div[@class='ubox-title']/span")->item(0)->textContent;
        $img = $pageXPath->query("//div[@class='upic-img']/img/@src")->item(0)->textContent;
        $name = $pageXPath->query("//p[@class='avatar__name']/span")->item(0)->textContent;
        $role = $pageXPath->query("//p[@class='avatar-rank']/span")->item(0)->textContent;
        $pantheonString = $pageXPath->query("//div[@class='avatar-stat']//div[@class='ubox-title']/a/@href");
        $pantheonId = null;
        if ($pantheonString->length) {
            $pantheonId = preg_replace('/\/community\/([0-9]+)$', '$1', $pantheonString->item(0)->textContent);
        }

        $memberPrestigeString = $pageXPath->query("//p[@class='avatar-rank b-tip']");
        if ($memberPrestigeString->length > 0){
            list($current, $max) = explode('/', $memberPrestigeString->item(0)->textContent);
            return array(
                'name' => trim($name),
                'nick' => trim($nick),
                'role_name' => trim($role),
                'pantheon_id' => $pantheonId,
                'img' => trim($img),
                'prestige' => array(
                    'current' => trim($current),
                    'max' => trim($max)
                )
            );
        } else {
            return false;
        }
    }

    /**
     * @param string $body
     *
     * @return bool
     */
    private function isAuthorized($body)
    {
        $pageXPath = new DOMXpath($this->getPageDom($body));
        $titleDom = $pageXPath->query("//head/title");
        if (!$titleDom->length) {
            var_dump($body);
            throw new RuntimeException('No title found!');
        }
        $titleText = $titleDom->item(0)->textContent;

        return !in_array($titleText, array('Вы не аутентифицированы', 'Нет доступа'));
    }

    private function isServerUpdating($body)
    {
        $pageXPath = new DOMXpath($this->getPageDom($body));
        $titleDom = $pageXPath->query("//head/title");

        if (!$titleDom->length) {
            var_dump($body);
            throw new RuntimeException('No title found!');
        }

        $titleText = $titleDom->item(0)->textContent;

        return $titleText == 'Skyforge - обновление';
    }
}
