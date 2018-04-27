<?php
namespace core;

class Controller extends Base
{
	protected $params;
	public $activity = null;
	public $headers = [];

	public function header($key)
	{
		isset($this->headers[strtoupper($key)]) ? $v = $this->headers[strtoupper($key)] : $v = '';
		return $v;
	}

	public function __construct($name,$params)
	{
        $name = strtolower($name);
        parent::__construct($name);
		$this->params = $params;
		$this->params = array_merge($this->params, $_POST);
		$this->params = array_merge($this->params, $_GET);
		$this->headers = self::_parseHeader();
		//控制器名称
		$this->name = $name;
	}

	public function isApp()
	{
		$status = isApp() ? 1 : 0;
		$this->ajaxReturn(['status'=>$status]);
	}


	private static function _parseHeader()
	{
		$headers = [];
		foreach ($_SERVER as $key => $value) {
		    if ('HTTP_' == substr($key, 0, 5)) {
		        $headers[strtoupper(str_replace('_', '-', substr($key, 5)))] = $value;
		    }
		}
		if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
		    $headers['AUTHORIZATION'] = $_SERVER['PHP_AUTH_DIGEST'];
		} elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
		    $headers['AUTHORIZATION'] = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
		}
		if (isset($_SERVER['CONTENT_LENGTH'])) {
		    $headers['CONTENT-LENGTH'] = $_SERVER['CONTENT_LENGTH'];
		}
		if (isset($_SERVER['CONTENT_TYPE'])) {
		    $headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
		}
		if (isset($_SERVER['CONTENT_LENGTH'])) {
		    $headers['CONTENT-LENGTH'] = $_SERVER['CONTENT_LENGTH'];
		}
		return $headers;
	}
	/**
	* @description 控制器默认方法
	*/
	public function index()
	{

	}

	protected function show($v)
	{
		if(ENVIRONMENT === 'production') return;
		var_dump($v);
		echo "<br>";
	}

	protected function fetch($fileName)
	{
	    //直接是该控制器对应目录下的页面
        if(strpos($fileName,'/') === false) {
            //如果是活动，传递filename是活动名称
            $this->activity ? $fileName .= '/index': $fileName = $this->name.'/'.$fileName;
        }
        //其他控制器对应目录下的页面
        list($controller,$view) = explode('/',$fileName);
        return $this->getView($controller, $view);
	}

	private function getView($controller,$view)
    {
        //由于config所在目录结构层次和Controller所在目录结构层次一致，所以可认为就是Controller对应的结构
        $basePath = Config::get('default.view');
        $basePath = rtrim($basePath,'/');
        $path = __DIR__.DS.$basePath.DS;
        $viewPath = $path.$controller.DS.$view.'.html';
        if(is_file($viewPath)) {
            return file_get_contents($viewPath);
        }
        throw new Exception("页面".DS.$controller.$view.DS.'index.html不存在');
    }

	/*
	 *跳转
	 *@param $url 目标地址
	 *@param $info 提示信息
	 *@param $sec 等待时间
	 *return void
	*/
	protected function redirect($url,$info=null,$sec=3)
	{
		 if(is_null($info)){
		  	header("Location:$url");
		 }else{
		  	echo"<meta http-equiv=\"refresh\" content=".$sec.";URL=".$url.">";
		  	echo $info;
		 }
		 exit;
	}

	protected function ajaxReturn($result,$options=0,$charset='utf-8')
    {
        $content = json_encode($result,$options);
        header("Content-Type:text/html; charset=$charset");
        echo $content;
        self::after("处理结束信息：$content",function($msg){
            Log::info($msg);
        });
    }
}