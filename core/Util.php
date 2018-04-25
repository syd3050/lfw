<?php
namespace core;

use core\Log;

class Util
{
    private static $_host = 'http://';
    private static $_opt = [
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ];
    const GET  = 'get';
    const POST = 'post';

    public static function buildQuery($data)
    {
        $r = '';
        foreach ($data as $name => $value) {
            $r .= "&$name=$value";
        }
        return ltrim($r,'&');
    }

    private static function _curlInit($url, $data = [], $headers = [], $method=self::GET)
    {
        //$url = self::$_host . $url;
        $ch  = curl_init();
        if($method == self::GET) {
            $queryString = self::buildQuery($data);
            strpos($url, '?') ? $url .= "&$queryString": $url .= "?$queryString";
        }else{
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        empty($headers) || curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //设置header
        
        curl_setopt_array($ch, self::$_opt);

        return $ch;
    }

    private static function _responseParse($response, $headerSize)
    {
        $result = [];
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

    public static function send($url, $data = [], $headers = [], $method=self::GET)
    {
        $ch = self::_curlInit($url, $data, $headers, $method);
        $response = curl_exec($ch);
        // 获得响应结果里的：头大小
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        // 状态码
        $r = ['code'=>curl_getinfo($ch, CURLINFO_HTTP_CODE)];
        
        $r = self::_responseParse($response, $headerSize);
        curl_close($ch);
        return $r;
    }

    /**
    * 批量访问url
    */
    public static function accessUrl($urls, $data = [], $callback = null)
    {
        $mh = curl_multi_init();
        $conn = array();
        $failure = array();
        $response = array();
        foreach ($urls as $i => $url) {
            $conn[$i] = curl_init($url);
            curl_setopt($conn[$i],CURLOPT_RETURNTRANSFER,true);
            curl_setopt($conn[$i], CURLOPT_TIMEOUT, 2);
            curl_setopt($conn[$i], CURLOPT_POSTFIELDS, $data);
            curl_setopt($conn[$i], CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($conn[$i], CURLOPT_SSL_VERIFYHOST, false);
            curl_multi_add_handle($mh,$conn[$i]);
        }

        //确保所有请求进程开始处理
        do {
            $mrc = curl_multi_exec($mh,$active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        //所有请求全部接收完毕后$active变为false
        while ($active && $mrc == CURLM_OK) {
           do {$mrc = curl_multi_exec($mh, $active);} while ($mrc == CURLM_CALL_MULTI_PERFORM);
           if (curl_multi_select($mh) !== -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }

            /*
                         //select可防止CPU空转，在select函数内部会监听FD，当在FD上有数据时才读取，其他情况下休眠
            if (curl_multi_select($mh) === -1) {
                //当select返回-1时，主动休眠100微秒再检测
                usleep(100);
            }
            //有数据进入后，在各个请求中不断接受数据
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            */



        }

        foreach ($urls as $i => $url) {
            $code = curl_getinfo($conn[$i], CURLINFO_HTTP_CODE);
            $content = curl_multi_getcontent($conn[$i]);
            if(is_callable($callback)) {
                $callback(['code'=>$code,'response'=>$content]);
            }
            //资源未就位，访问失败
            if($code != '200' && $code != '304') {
                Log::error("url:$url,status:$code");
                $failure[] = $url;
            }else{
                $response[] = $content;
            }
            curl_close($conn[$i]);
            curl_multi_remove_handle($mh,$conn[$i]);
        }
        curl_multi_close($mh);
        return ['lost'=>$failure,'response'=>$response];
    }

    public static function paramParser($param,$delimiter)
    {
        $r = '';
        foreach ($param as $value)
        {
            $poss = strpos($value,$delimiter);
            if($poss !== false)
            {
                $ck = explode('_',$value);
                if(count($ck) == 2)
                {
                    $r .= $ck[0].'='.$ck[1].';';
                }
            }
        }
        return $r;
    }

    /**
     * 非递归遍历目录
     * 使用方法
        $dirArr = ['/apps/release/php'];
        $result = Util::scanFiles($dirArr);
        while (is_callable($result)) {
            $result = $result();
        }
     * @param array $dirArr
     * @param array $files
     * @return array|\Closure  目录下所有文件路径
     */
    public static function scanFiles($dirArr,$files=[]) {
        if(empty($dirArr)) {
            return $files;
        }
        foreach ($dirArr as $k=>$dir) {
            $handle = opendir($dir);
            if($handle){
                unset($dirArr[$k]);
                while(($fl = readdir($handle)) !== false) {
                    $temp = $dir . DIRECTORY_SEPARATOR . $fl;
                    if (is_dir($temp) && $fl != '.' && $fl != '..') {
                        $dirArr[] = $temp;
                    } else if($fl != '.' && $fl != '..'){
                        $files[] = $temp;
                    }
                }
            }
        }

        return function() use($dirArr, $files) {
            return self::scanFiles($dirArr, $files);
        };
    }

}