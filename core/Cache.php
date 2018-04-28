<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/28
 * Time: 14:01
 */

namespace core;


use core\cache\Redis;

class Cache extends Base
{
    /**
     * @var null| Redis
     */
    private static $_handler = null;
    private static $_config = [
        'type'     => 'redis',
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'password' => '111111',
    ];

    private static function verify($config, &$info)
    {
        if(!isset($config['type'])) {
            $info['msg'][] = '缺少缓存类型信息';
            return false;
        }
        return true;
    }

    private static function _init()
    {
        $info = ['msg'=>[]];
        $config = Config::get('cache');
        empty($config) || self::$_config = array_merge(self::$_config,$config);
        if(!self::verify(self::$_config,$info))
            throw new Exception("配置错误,错误信息：".json_encode($info));
        $type = self::$_config['type'];
        $namespace = Config::get('namespace.cache');
        if(empty($namespace))
            throw new Exception("model命名空间没有配置");
        $class = $namespace.$type;
        if(!class_exists($class))
            throw new Exception("对应类型缓存不存在");
        self::$_handler = new $class(self::$_config);
        return self::$_handler;
    }

    public static function set($k,$v,$timeout=0)
    {
        return self::_init()->set($k,$v,$timeout);
    }

    public static function setNx($k,$v)
    {
        return self::_init()->setNx($k,$v);
    }

    public static function get($k)
    {
        return self::_init()->get($k);
    }

    public static function delete($k)
    {
        self::_init()->delete($k);
    }

    public static function exists($k)
    {
        return self::_init()->exists($k);
    }
}