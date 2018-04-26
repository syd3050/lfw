<?php
namespace core;

/**
 * 日志处理
 * @method void log($msg) static
 * @method void error($msg) static
 * @method void info($msg) static
 * @method void sql($msg) static
 * @method void notice($msg) static
 * @method void alert($msg) static
*/
class Log
{
    const LOG    = 'log';
    const ERROR  = 'error';
    const INFO   = 'info';
    const SQL    = 'sql';
    const NOTICE = 'notice';
    const ALERT  = 'alert';
    const DEBUG  = 'debug';

	// 日志写入驱动
    protected static $driver;
    protected static $config;
    // 日志类型
    protected static $type = ['log', 'error', 'info', 'sql', 'notice', 'alert', 'debug'];

    /**
     * 日志初始化,默认以文件为驱动
     * @param array $config
     * @throws Exception
     */
    public static function init($config = [])
    {
        $type         = isset($config['type']) ? $config['type'] : 'File';
        $class        = false !== strpos($type, '\\') ? $type : '\\core\\log\\driver\\' . ucwords($type);
        self::$config = $config;
        unset($config['type']);
        if (class_exists($class)) {
            self::$driver = new $class($config);
        } else {
            throw new Exception('class not exists:' . $class);
        }
    }

    public static function record($msg, $type = 'log')
    {
    	empty(self::$driver) && self::init();
    	return self::$driver->save([$type=>$msg]);
    }

	public static function __callStatic($method, $args)
	{
        if (in_array($method, self::$type)) {
            array_push($args, $method);
            return call_user_func_array('\\core\\Log::record', $args);
        }
	}
}