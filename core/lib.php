<?php

if (!function_exists("fastcgi_finish_request")) {
  function fastcgi_finish_request()  {

  }
}

if(!function_exists("isAPP")) {
  function isAPP() {
	  	$from = empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI'] . '&';
	  	$from .= empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'] . '&';
	  	foreach (array('app', 'iso', 'android') as $item) {
	    	if (stripos($from, 'from=' . $item . '&') !== FALSE) 
	    		return TRUE;
	  	}
	  	return FALSE;
	}
}

if(!function_exists("randStr")) {
    function randStr($len=10) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $string = time();
        for(;$len>=1;$len--) {
            $position = rand()%strlen($chars);
            $position2 = rand()%strlen($string);
            $string = substr_replace($string,substr($chars,$position,1),$position2,0);
        }
        return $string;
    }
}




