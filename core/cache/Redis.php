<?php
namespace core\cache;

class Redis
{
    private $_redis = null;

    public function __construct($config)
    {
        $redis = new \Redis();
        $redis->connect($config['host'], $config['port']);
        isset($config['password']) && $redis->auth($config['password']);
        $this->_redis = $redis;
    }

    public function set($k,$v,$timeout=0)
    {
        return $this->_redis->set($k,$v,$timeout);
    }

    public function get($k)
    {
        return $this->_redis->get($k);
    }

    public function delete($k)
    {
        $this->_redis->delete($k);
    }

    public function setNx($k,$v)
    {
        return $this->_redis->setnx($k,$v);
    }

    public function hSet($key,$hashKey,$value)
    {
        return $this->_redis->hSet($key,$hashKey,$value);
    }

    public function hGet($key,$hashKey)
    {
        return $this->_redis->hGet($key,$hashKey);
    }

    public function hDel($key,$hashKey)
    {
        return $this->_redis->hDel($key,$hashKey);
    }

    public function hMSet($key,$map)
    {
        return $this->_redis->hMset($key,$map);
    }

    public function exists($k)
    {
        return $this->_redis->exists($k);
    }
}