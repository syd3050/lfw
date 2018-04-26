<?php
namespace core;

class Base
{

    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public static function exception($msg, $code)
    {
        throw new Exception($msg, $code);
    }

	protected static function showError($msg)
	{
	    header("Content-Type:text/html; charset=utf-8");
	    $script = "<script>alert('$msg')</script>";
	    echo $script;
	    self::after($msg,function($msg){
	    	Log::error($msg);
	    });
	}

	protected static function after($msg,$callback)
	{
		if (function_exists('fastcgi_finish_request')) {
	    	set_time_limit(10);  //10 秒
            fastcgi_finish_request();
        }
        if(is_callable($callback)) {
        	$callback($msg);
        }
        exit;
	}
}