<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   15.01.2015
 */

namespace Erliz\SkyforgeBundle\Service;


use DOMDocument;
use DOMXPath;
use Erliz\SkyforgeBundle\Extension\Client\CookieJar;
use GuzzleHttp\Client as Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Post\PostBodyInterface;
use GuzzleHttp\Subscriber\Cookie;
use GuzzleHttp\Subscriber\History;
use RuntimeException;

class ParseService
{
    /** @var Client */
    private $client;
    /** @var CookieJar */
    private $cookieJar;
    /** @var string */
    private $cookieFile;
    /** @var array */
    private $authData;
    /** @var bool */
    private $tryAuthorize = false;
    /** @var RegionService */
    private $regionService;
    /** @var History */
    private $history;

    public function __construct(RegionService $regionService, $cachePath)
    {
        $this->cookieFile = $cachePath . 'curl/cookie_%s.json';
        $this->regionService = $regionService;
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
            'Host'            => $this->regionService->makeProjectDomain($this->regionService->getRegion()),
            'Origin'          => 'https://' . $this->regionService->makeProjectDomain($this->regionService->getRegion())
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

        $token = $this->cookieJar->getCookieValueByName('csrf_token', $this->regionService->getProjectDomain());

        if (!$token) {
            $this->authorize();
            $token = $this->cookieJar->getCookieValueByName('csrf_token', $this->regionService->getProjectDomain());
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
        throw new RuntimeException('Deprecated function');

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
//        $this->cookieJar = new CookieJar();
//        if (is_readable($this->getCookieFile())) {
//            $fileData = file_get_contents($this->getCookieFile());
//            if (!json_decode($fileData)) {
//                file_put_contents($this->getCookieFile(), json_encode($this->convertJarFromNetscapeToJson($fileData)));
//            }
//        }
//        exit;
        $this->cookieJar = new CookieJar($this->getCookieFile());
        $this->history = new History();

//        if (!$cookieless) {
//            $this->cookieFile->load($this->cookieFile);
//        }


        $this->client = new Client(array('defaults'=>array('subscribers'=>array(new Cookie($this->cookieJar), $this->history))));
    }

    private function exportCookie()
    {
        $this->cookieJar->save($this->getCookieFile());
    }

    private function authorize()
    {
        $this->createClient(true, true);

        $authOptions = $this->regionService->getAuthOptions();

        /** @var ResponseInterface $response */
        if ($authOptions['method'] == 'GET') {
            $response = $this->client->get($authOptions['url']);
        } elseif ($authOptions['method'] == 'POST') {
            $response = $this->client->post($authOptions['url'], array('body'=>$authOptions['data']));
        }

        $this->client->get($this->regionService->getProjectUrl() . 'skyforgenews');

        $this->exportCookie();

//        foreach ($this->history->getRequests() as $request) {
//            echo $request->getUrl() ."\n";
//        }

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
                $userId = $this->getIdFromUrl($nameBlock->item(0)->textContent);
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

    public function getCommunities ($body)
    {
        $dom = $this->getPageDom($body);

        $pageXPath = new DOMXpath($dom);


        $communitiesDom = $pageXPath->query(
            "//div[@class='card-wrap']"
        );
        if (!$communitiesDom->length) {
            throw new RuntimeException('No communities found on page', 50);
        }

        $storage = array();

        /** @var \DOMElement $menuItemDom */
        foreach ($communitiesDom as $menuItemDom) {
            $elementDom = new DOMDocument();
            $elementDom->loadHTML($menuItemDom->ownerDocument->saveXML($menuItemDom));

            $infoXPath = new DOMXPath($elementDom);

            $pantheonTag = $infoXPath->query("//span[@class='guild-name-pre ']");
            // need only pantheons
            if (!$pantheonTag->length) {
                continue;
            }

            $itemDataNodes = array(
                'pic' => $infoXPath->query("//div[@class='upic-img']/a/img[@class=' b-tip']/@src")->item(0)->textContent,
                'name' => $nameBlock = $infoXPath->query("//div[@class='ubox-title']/a")->item(0)->textContent,
                'id' => $this->getIdFromUrl(
                    $infoXPath->query("//div[@class='ubox-title']/a/@href")->item(0)->textContent
                )
            );

            foreach ($itemDataNodes as $key => $item) {
                $itemDataNodes[$key] = mb_convert_encoding(trim($item), 'cp1252', 'utf-8');
            }

            $storage[$itemDataNodes['id']] = (object) $itemDataNodes;
        }

        return $storage;
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
            $itemDataNodes['id'] = $this->getIdFromUrl($itemDataNodes['wall_url']);
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
    private function getIdFromUrl($wallUrl)
    {
        return preg_replace('/.*\/(?:wall|community)\/([0-9]+)/', '$1', $wallUrl);
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
     * @param string $body
     *
     * @return array|bool
     */
    public function getDataFromProfilePage($body)
    {
        $pageXPath = new DOMXpath($this->getPageDom($body));
        if (!$pageXPath->query("//div[@class='s-avatar_info']")->item(0) || !$pageXPath->query("//div[@class='s-avatar_info']")->item(0)->hasChildNodes()) {
            throw new RuntimeException('Profile have no avatar data', 403);
        }

        $nick = $pageXPath->query("//div[@class='ubox-title']/span")->item(0)->textContent;
        $img = $pageXPath->query("//div[@class='upic-img']/img/@src")->item(0)->textContent;
        if ($pageXPath->query("//p[@class='avatar__name']/span")->item(0)) {
            $name = $pageXPath->query("//p[@class='avatar__name']/span")->item(0)->textContent;
        } else {
            $name = '';
        }
        $role = $pageXPath->query("//p[@class='avatar-rank']/span")->item(0)->textContent;
        $pantheonString = $pageXPath->query("//div[@class='avatar-stat']//div[@class='ubox-title']/a/@href");
        $pantheonId = null;
        if ($pantheonString->length) {
            $pantheonId = $this->getIdFromUrl($pantheonString->item(0)->textContent);
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

        return !in_array(
            $titleText,
            array('Вы не аутентифицированы', 'Нет доступа', 'You are not logged in', 'No access')
        );
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

    /**
     * @param string $fileData
     *
     * @return array
     */
    private function convertJarFromNetscapeToJson($fileData)
    {
        $cookies = array();

        $lines = explode("\n", $fileData);

        // iterate over lines
        foreach ($lines as $line) {

            // we only care for valid cookie def lines
            if (isset($line[0]) && substr_count($line, "\t") == 6) {

                // get tokens in an array
                $tokens = explode("\t", $line);

                // trim the tokens
                $tokens = array_map('trim', $tokens);
                $isHttpOnly = false;
                $cookie = array();

                $domainFlags = explode('_', $tokens[0]);
                if ($domainFlags[0] == '#HttpOnly') {
                    $tokens[0] = $domainFlags[1];
                    $isHttpOnly = true;
                }

                // Extract the data
                $cookie['Domain'] = $tokens[0];
                $cookie['Discard'] = $tokens[1];
                $cookie['Path'] = $tokens[2];
                $cookie['Secure'] = $tokens[3];

                // Convert date to a readable format
                $cookie['Expires'] = date('@t', $tokens[4]);

                $cookie['Name'] = $tokens[5];
                $cookie['Value'] = $tokens[6];
                $cookie['HttpOnly'] = $isHttpOnly;

                // Record the cookie.
                $cookies[] = $cookie;
            }
        }

        return $cookies;
    }
}
