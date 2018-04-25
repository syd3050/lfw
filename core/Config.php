<?php
namespace core;

class Config extends Base
{
    private static function _init()
    {
        $file = APP_PATH.'config.php';
        if(is_file($file))
            return include $file;
        self::exception('配置文件app\config.php不存在',Type::F_ERROR);
    }

    /**
     * 获取配置项
     * @param string $key 支持xxx,xxx.xxx等形式
     * @return mixed|null
     */
    public static function get($key)
    {
        $config = self::_init();
        if(isset($config[$key]))
            return $config[$key];
        if(!strpos($key,'.'))
            return null;
        //解析key
        $keys = explode('.',$key);
        foreach ($keys as $k) {
            if(!isset($config[$k]))
                return null;
            $config = $config[$k];
        }
        return $config;
    }
}