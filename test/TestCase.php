<?php

require_once '../vendor/autoload.php';

$_GET['UIS_DEBUG_MODE'] = -1;

$main_logger = \FFan\Std\Logger\LogHelper::getLogger(__DIR__ .'/runtime/logs');
\FFan\Std\Logger\LogHelper::setMainLogger($main_logger);
\FFan\Std\Console\Debug::init();

$main_logger->warning('test');

\FFan\Std\Console\Debug::displayDebugMessage(array(1,3,4,5));