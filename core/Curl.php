<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/25
 * Time: 17:57
 */

namespace core;


class Curl
{
    private $_opt = [
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ];
    private $_ch = null;
    const GET  = 'get';
    const POST = 'post';

    public function __construct()
    {
        $this->_ch = curl_init();
    }

    public function get($url,$data)
    {
        $queryString = Util::buildQuery($data);
        strpos($url, '?') ? $url .= "&$queryString": $url .= "?$queryString";
        $v = $this->send($url);
        return $v;
    }

    public function post($url,$data)
    {
        curl_setopt($this->_ch, CURLOPT_POST, true);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $data);
        $v = $this->send($url);
        return $v;
    }

    public function setOpt($k,$v)
    {
        $this->_opt[$k] = $v;
    }

    public function setOpts($opts)
    {
        $this->_opt = array_merge($this->_opt,$opts);
    }

    private function send($url)
    {
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        curl_setopt_array($this->_ch,$this->_opt);
        $response = curl_exec($this->_ch);
        $r = $this->_responseParse($response);
        curl_close($this->_ch);
        return $r;
    }

    private function _responseParse($response)
    {
        // 获得响应结果里的：头大小
        $headerSize = curl_getinfo($this->_ch, CURLINFO_HEADER_SIZE);
        // 状态码
        $result = ['code'=>curl_getinfo($this->_ch, CURLINFO_HTTP_CODE)];
        // 根据头大小去获取头信息内容
        $responseHead = substr($response, 0, $headerSize);
        //payload
        $response = substr($response,$headerSize);
        //获取返回的Cookie
        $cookie = '';
        $headArr = explode("\r\n", $responseHead);
        foreach ($headArr as $loop) {
            $pos = strpos($loop, "Cookie");
            if($pos !== false){
                $tmp = substr($loop,$pos+7);
                $tmp .= ';';
                $cookie .= $tmp;
            }
        }
        empty($cookie) || $result['cookie'] = rtrim($cookie,';');
        $result['header']  = $responseHead;
        $result['content'] = $response;
        return $result;
    }

}