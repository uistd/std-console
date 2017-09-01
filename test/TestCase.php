<?php

use FFan\Std\Console\Debug;
use FFan\Std\Logger\LogHelper;

require_once '../vendor/autoload.php';

$_GET['UIS_DEBUG_MODE'] = -1;

$main_logger = new \FFan\Std\Logger\FileLogger(__DIR__ .'/runtime/logs');
Debug::init();

$log_router = LogHelper::getLogRouter();
$log_router->warning('test');

Debug::getConsole(Debug::IO_TAB_NAME)->log('This is test io message!');
Debug::debug('This is debug msg');
Debug::displayDebugMessage(array(1,3,4,5));
