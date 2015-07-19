<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   23.04.2015
 */

namespace Erliz\SkyforgeBundle\Extension\Client;


use GuzzleHttp\Cookie\FileCookieJar as BaseCookieJar;

class CookieJar extends BaseCookieJar
{
    /**
     * @param string $name
     *
     * @return null|string
     */
    public function getCookieValueByName($name, $domain = null)
    {
        foreach ($this->toArray() as $cookie) {
            if($cookie['Name'] == $name) {
                if ($domain && $domain != $cookie['Domain']) {
                    continue;
                }

                return $cookie['Value'];
            }
        }

        return null;
    }
}
