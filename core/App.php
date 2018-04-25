<?php
namespace core;

use core\Log;
use core\Base;

class App extends Base
{
	private static $_url;

	private static function _parse()
	{
		self::$_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$params = trim($_SERVER["REQUEST_URI"],'/');
		$params = explode('/',$params);
		if(count($params) < 2) {
		    $controller = Config::get('default.controller');
		    $action = Config::get('default.action');
        }else{
            //控制器
            $controller = array_splice($params,0,1)[0];
            //方法
            $action = array_splice($params,0,1)[0];
        }
		//解析保存其他参数,参数格式：key_value
		$otherParams = [];
		foreach ($params as $value) {
			if(strpos($value,'_')) {
				list($k,$v) = explode('_', $value);
				$otherParams[$k] = $v;
			}
		}
		return [$controller,$action,$otherParams];
	}

	/**
	* @description 分派处理请求
	* @param string $controller
	* @param string $action
	* @param array  $otherParams
	* @return string
	*/
	private static function _dispatch($controller,$action,$otherParams)
	{
		//控制器首字母转为大写
		$controllerName = ucwords($controller);
		//构建控制器
        $controller = Config::get(Type::K_NAMESPACE.'.controller').$controllerName;
		class_exists($controller) || self::showError('路径格式错误');
		$controllerObj = new $controller($controllerName,$otherParams);

		//如果方法不存在，则认为该参数为活动英文名称，且将调用默认方法index
		if(!method_exists($controllerObj,$action)) {
			$controllerObj->activity = $action;
			$action = 'index';
		}
		return $controllerObj->$action();
	}

	private static function _sessionInit()
	{
		isset($_GET['PHPSESSID']) && session_id($_GET['PHPSESSID']);
		//当禁用cookie时将session_id带在url上,只对不以http://开头的地址有效
		ini_set('session.use_trans_sid', 1);
		ini_set('session.use_only_cookies', 0);
		//禁止cookie中的sessionid被js读取
		ini_set('session.cookie_httponly', 1 );
		session_start(); 
	}

	/**
	 * @description 解析并处理请求
	 */
	public static function run()
	{
		self::_sessionInit();
		list($controller,$action,$otherParams) = self::_parse();
		$exception = false;
		try{
			//派发请求,获取处理结果
			$content = self::_dispatch($controller,$action,$otherParams);
		}catch(\Exception $e) {
			$exception = true;
			$content = $e->getMessage();
		}
		header("Content-Type:text/html; charset=utf-8");
		echo $content;
		$exception ? self::after('处理异常,信息：'.$content,function($msg){
			Log::error($msg);
		}) : self::after('处理结束',function($msg){
			Log::info($msg);
		});
	}
}