<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/20
 * Time: 18:45
 */

namespace app\controller;

use core\Config;
use core\Controller;

class Index extends Controller
{
    public function index()
    {
        return '123';
    }

    public function test()
    {
        $t = Config::get('default');
        return var_export($t);
    }
}