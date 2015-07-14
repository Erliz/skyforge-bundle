<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   23.04.2015
 */

namespace Erliz\SkyforgeBundle\Extension\Client;


use GuzzleHttp\Cookie\CookieJar as BaseCookieJar;

class CookieJar extends BaseCookieJar
{
    /**
     * @param string $name
     *
     * @return null|string
     */
    public function getCookieValueByName($name)
    {
        foreach ($this->toArray() as $cookie) {
            if($cookie['Name'] == $name) {
                return $cookie['Value'];
            }
        }

        return null;
    }
}
