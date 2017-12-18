<?php
use UiStd\Console\Debug;
use UiStd\Event\EventDriver;
use UiStd\Event\EventManager;
use UiStd\Logger\LogHelper;

/**
 * 打印
 */
function debug()
{
    $logger = LogHelper::getLogRouter();
    foreach (func_get_args() as $each_arg) {
        $log_str = Debug::varFormat($each_arg);
        $logger->debug('[DEBUG]'. $log_str);
    }
}

/**
 * 打印一个变更 ，并且立即结束
 * @param $expression
 * @param null $_
 */
function dd($expression, $_ = null)
{
    foreach (func_get_args() as $each_arg) {
        echo Debug::varFormat($each_arg), PHP_EOL;
    }
    uis_die();
}

/**
 * 便捷的获取配置
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function config($key, $default = null)
{
    return \UiStd\Common\Config::get($key, $default);
}

/**
 * print_r函数 别名
 */
function pr()
{
    call_user_func_array('print_r', func_get_args());
}

/**
 * 打印从请求到现在一共花的时间
 */
function debugTotalTime()
{
    if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
        return;
    }
    $cost_time = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . 'ms';
    LogHelper::getLogRouter()->info('TOTAL_TIME:' . $cost_time);
}

/**
 * 经过包装过的exit函数
 * @param int|string $status
 */
function uis_exit($status = 0)
{
    uis_die($status);
}

/**
 * 经过包装过的exit函数
 * @param int|string $status
 */
function uis_die($status = 0)
{
    EventManager::instance()->trigger(EventDriver::EVENT_EXIT);
    die($status);
}
