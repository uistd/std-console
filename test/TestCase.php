<?php

use FFan\Std\Console\Debug;
use FFan\Std\Logger\LogHelper;

require_once '../vendor/autoload.php';

$_GET['UIS_DEBUG_MODE'] = -1;

$main_logger = LogHelper::getLogger(__DIR__ .'/runtime/logs');
LogHelper::setMainLogger($main_logger);
Debug::init();

$main_logger->warning('test');

Debug::getConsole(Debug::IO_TAB_NAME)->log('This is test io message!');
Debug::debug('This is debug msg');
Debug::displayDebugMessage(array(1,3,4,5));
