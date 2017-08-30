<?php

namespace FFan\Std\Console;

use FFan\Std\Logger\LogLevel;

/**
 * Class LogRouter 日志分离
 * @package FFan\Std\Console
 */
class LogRouter extends \FFan\Std\Logger\LogRouter
{
    /**
     * 路由
     * @param int $log_level
     * @param string $message
     * @return int
     */
    function route($log_level, $message)
    {
        $log_console = Debug::getConsole('LOG');
        $log_console->log('[' . LogLevel::levelName($log_level) . ']' . $message);

        if ('[' !== $message[0]) {
            return 0;
        }
        $right_pos = strpos($message, ']');
        if (false === $right_pos) {
            return 0;
        }
        $log_flag = substr($message, 1, $right_pos);
        switch ($log_flag) {
            case 'ERROR':
                Debug::getConsole('ERROR')->log($message);
                break;
            case 'I/O':
                $message .= PHP_EOL . str_repeat('=', 256) . PHP_EOL;
                Debug::getConsole(Debug::IO_TAB_NAME)->log($message);
                break;
            default:
                Debug::getConsole('LOG')->log($message);
        }
        return 0;
    }
}
