<?php
namespace app\controller;

use core\Controller;
use core\Util;

class Act extends Controller
{
	/**
	*  控制器默认方法,若方法不存在，就会调用这个方法
	*  
	*/
	public function index()
	{
		if(!empty($this->activity)) {
			return $this->fetch($this->activity);
		}
		return 'Hello world!';
	}

	public function tabb()
    {

    }

	public function t()
    {
        return $this->fetch('tabb');
    }

	/**
     * 获取图片验证码路由
     * @return mixed
     */
    public function getCodeImg()
    {
        $url = 'http://172.28.66.228:8030/common/getCodeImg';
        $user_Agent = $this->header("User-Agent");
        $referer = $this->header("Referer");
        $ajax = $this->header("X-Requested-With");

        $headers = [
            "QBENV:development",
            "Accept:application/json, text/javascript, */*;",
            "User-Agent:".$user_Agent,
        ];

        $result = Util::send($url, [], $headers);
        if(isset($result['cookie']))
        {
        	$_SESSION['client_cookie'] = $result['cookie'];
        }
        if(empty($ajax) || $ajax != 'XMLHttpRequest') {
            return $result['content'];
        }else {
            $r = (array)json_decode($result['content']);
            return $r;
        }
    }

    /**
     * 获取短信验证码路由
     * @return mixed
     */
    public function getIdentifyingCode()
    {
        $mobilePhone = $this->params['mobilePhone'];
        $imageCode = $this->params['imageCode'];

        $user_Agent = $this->header("User-Agent");
        $ajax = $this->header("X-Requested-With");

        $url = 'http://172.28.66.228:8030/register/do_identifyingCode';

        $post_data = [
            'mobilePhone'=>$mobilePhone,
            'imageCode'=>$imageCode,
        ];
        $headers = [
            "QBENV:development",
            "Accept:application/json, text/javascript, */*;",
            "Cookie:".$_SESSION['client_cookie'],
            "User-Agent:".$user_Agent
        ];
        $result = Util::send($url, $post_data, $headers, Util::POST);
        if(empty($ajax) || $ajax != 'XMLHttpRequest') {
            return $result['content'];
        }else {
            $r = (array)json_decode($result['content']);
            return $r;
        }
    }

    /**
     * 活动发布完后，真正使用时注册表单的路由
     * @return mixed
     */
    public function doRegister()
    {
        return $this->commonRegister();
    }

    private function commonRegister()
    {
        $mobilePhone = $this->params['mobilePhone'];
        $imageCode = $this->params['imageCode'];
        $identifyingCode = $this->params['identifyingCode'];
        $pwd = $this->params['pwd'];

        $url = 'http://172.28.66.228:8030/register/do_register/act';
        $user_Agent = $this->header("User-Agent");
        $referer = $this->header("Referer");
        $ajax = $this->header("X-Requested-With");

        //将客户端的URL参数转换为cookie发给接口端
        $cookie = '';
        if(!empty($referer))
        {
            $pos = strpos($referer,'?');
            //参数是key1_value1/key2_value2/key3_value3的样式
            if($pos === false)
            {
                $querys = substr($referer,strpos($referer,'/')+1);
                $arr = explode("/",$querys);
            }else{
                $querys = substr($referer,$pos+1);
                $arr = explode("&",$querys);
            }
            $cookie = Util::paramParser($arr,'_');
        }

        $post_data = [
            'mobilePhone'=>$mobilePhone,
            'imageCode'=>$imageCode,
            'identifyingCode'=>$identifyingCode,
            'pwd'=>$pwd,
        ];
        $headers = [
            "QBENV:development",
            "Accept:application/json, text/javascript, */*;",
            "User-Agent:".$user_Agent
        ];
        empty($cookie) || $headers[] = "Cookie:".$cookie.";".$_SESSION['client_cookie'];
        $result = Util::send($url, $post_data, $headers, Util::POST);
        if(empty($ajax) || $ajax != 'XMLHttpRequest') {
            return $result['content'];
        }else {
            $r = (array)json_decode($result['content']);
            return $r;
        }
    }

}