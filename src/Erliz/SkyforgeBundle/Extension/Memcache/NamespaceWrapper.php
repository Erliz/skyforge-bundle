<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   18.08.2015
 */

namespace Erliz\SkyforgeBundle\Extension\Memcache;


use KuiKui\MemcacheServiceProvider\SimpleWrapper;

class NamespaceWrapper extends SimpleWrapper
{
    const DEFAULT_EXPIRATION = 1800;

    public function set($key, $data, $expiration = self::DEFAULT_EXPIRATION, $compress = null)
    {
        return parent::set($key, $data, $expiration, $compress);
    }

    public function get($key, \Closure $fallback = null, $expiration = self::DEFAULT_EXPIRATION, $compress = null)
    {
        return parent::get($this->app['memcache.namespace'] . $key, $fallback, $expiration, $compress);
    }

    public function delete($key)
    {
        return parent::delete($this->app['memcache.namespace'] . $key);
    }
}
