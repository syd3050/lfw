<?php
define('ENVIRONMENT', isset($_SERVER['HTTP_QBENV']) ? $_SERVER['HTTP_QBENV'] : 'production');
header("HTTP_QBENV:".ENVIRONMENT);

defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('ROOT_PATH') or define('ROOT_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
defined('APP_PATH') or define('APP_PATH', ROOT_PATH . 'app' . DS);
defined('LOG_PATH') or define('LOG_PATH', ROOT_PATH . 'log' . DS);
defined('START_TIME') or define('START_TIME', microtime(true));
defined('START_MEM') or define('START_MEM', memory_get_usage());

require_once "./core/lib.php";
require_once "./core/Loader.php";
//注册自动加载
core\Loader::register();
// 执行应用
core\App::run();


