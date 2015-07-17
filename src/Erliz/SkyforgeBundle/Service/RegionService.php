<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   24.04.2015
 */

namespace Erliz\SkyforgeBundle\Service;


use Erliz\SilexCommonBundle\Service\ApplicationAwareService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class RegionService
 * @package Erliz\SkyforgeBundle\Service
 *
 * Must be init in before action
 */
class RegionService extends ApplicationAwareService
{
    const RU_REGION = 'ru';
    const EU_REGION = 'eu';
    const NA_REGION = 'na';
    const DB_PREFIX = 'skyforge';
    const MAILRU_DOMAIN_TEMPLATE = 'portal.sf.mail.ru';
    const MYCOM_DOMAIN_TEMPLATE  = '%s.portal.sf.my.com';

    private $region;

    /**
     * @param string $region
     */
    public function setRegion($region)
    {
        if (!in_array($region, $this->getRegionList())) {
            throw new InvalidArgumentException(
                sprintf('Region must be from %s "%s" given', json_encode($this->getRegionList()), $region)
            );
        }

        $this->region = $region;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getDbConnectionNameByRegion()
    {
        if (!$this->region) {
            throw new RuntimeException('Set region before getting db connection name');
        }

        return $this::makeDbConnectionName($this->region);
    }

    /**
     * @return array
     */
    public function getCredentials()
    {
        $config = $this->getApp()['config']['skyforge'];

        $credentials = $this->getRegion() == $this::RU_REGION ? $config['statistic'] : $config['mycom'];

        return $credentials;
    }

    /**
     * @param string $region
     *
     * @return string
     */
    static public function makeDbConnectionName($region)
    {
        return sprintf('%s_%s', self::DB_PREFIX, $region);
    }

    /**
     * @param string $region
     *
     * @return string
     */
    static public function makeProjectUrl($region)
    {
        return sprintf('https://%s/', self::makeProjectDomain($region));
    }

    /**
     * @return string
     */
    public function getProjectUrl()
    {
        return sprintf('https://%s/', self::makeProjectDomain($this->getRegion()));
    }

    public function getAuthOptions()
    {
        if($this->getRegion() == $this::RU_REGION) {
            return array(
                'method' => 'GET',
                'url' => sprintf(
                    'https://auth.mail.ru/cgi-bin/auth?Login=%s&Password=%s&Page=%sskyforgenews',
                    $this->getCredentials()['login'],
                    $this->getCredentials()['password'],
                    $this->getProjectUrl()
                )
            );
        } else {
            return array(
                'method' => 'POST',
                'url' => 'https://auth-ac.my.com/auth',
                'data' => array(
                    'login' => $this->getCredentials()['login'],
                    'password' => $this->getCredentials()['password'],
                    'continue' => $this->getProjectUrl() . 'skyforgenews?auth_result=success',
                    'failure' => $this->getProjectUrl() . 'skyforgenews?auth_result=failed',
                    'nosavelogin' => '0'
                ),
            );
        }
    }

    /**
     * @param string $region
     *
     * @return string
     */
    static public function makeProjectDomain($region)
    {
        return sprintf(self::getDomainTemplate($region), $region);
    }

    /**
     * @return array
     */
    static public function getRegionList()
    {
        return array(
            self::RU_REGION,
            self::EU_REGION,
            self::NA_REGION
        );
    }

    /**
     * @param string $region
     *
     * @return string
     */
    static private function getDomainTemplate($region) {
        return $region == self::RU_REGION ? self::MAILRU_DOMAIN_TEMPLATE : self::MYCOM_DOMAIN_TEMPLATE;
    }
}
