<?php

return [
    'default' => [
        'controller'=>'Index',
        'action' => 'index',
        //视图文件所在路径
        'view' => '../../act',   //针对活动的设置
        //'view' => '../views/', //默认设置
    ],
    'namespace' => [
        'app'  => 'app\\',
        'core' => 'core\\',
        'controller' => 'app\\controller\\',
        'log'  =>  'core\\log\\',
    ],
];