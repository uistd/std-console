<?php

namespace UiStd\Console;

use UiStd\Logger\LoggerBase;
use UiStd\Logger\LogLevel;

/**
 * Class LogRouter 日志分离
 * @package UiStd\Console
 */
class LogRouter extends LoggerBase
{
    /**
     * 收到日志
     * @param int $log_level
     * @param string $content
     */
    public function onLog($log_level, $content)
    {
        $log_console = Debug::getConsole('LOG');
        $log_console->log('[' . LogLevel::levelName($log_level) . ']' . $content);

        if ('[' !== $content[0]) {
            return;
        }
        $right_pos = strpos($content, ']');
        if (false === $right_pos) {
            return;
        }
        $log_flag = substr($content, 1, $right_pos - 1);
        switch ($log_flag) {
            case 'ERROR':
            case 'DEBUG':
                Debug::getConsole($log_flag)->log($content);
                break;
            case 'I/O':
                $content .= PHP_EOL;
                Debug::getConsole(Debug::IO_TAB_NAME)->log($content);
                break;
            default:
                Debug::getConsole('LOG')->log($content);
        }
    }
}
