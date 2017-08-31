<?php

namespace FFan\Std\Console;

use FFan\Std\Common\Ip as FFanIp;
use FFan\Std\Common\Str as FFanStr;
use FFan\Std\Common\Utils;
use FFan\Std\Logger\LogHelper;

/**
 * Class Debug 调试类
 * @package FFan\Std\Console
 */
class Debug
{
    const IO_TAB_NAME = 'I/O';

    /**
     * 显示所有错误
     */
    const MODE_DEBUG_ERROR = 1;

    /**
     * 显示I/O调试信息
     */
    const MODE_DEBUG_IO = 2;

    /**
     * 显示php trace info
     */
    const MODE_DEBUG_TRACE = 4;

    /**
     * 显示异常详情
     */
    const MODE_DEBUG_EXCEPTION = 8;

    /**
     * 显示跟踪信息的参数详情
     */
    const MODE_DEBUG_ARGS = 16;

    /**
     * @var int 模式值
     */
    private static $debug_mode = 0;

    /**
     * @var array logger
     */
    private static $console_arr;

    /**
     * @var int 开始时间
     */
    private static $start_time;

    /**
     * @var array 计时器数组
     */
    private static $timer_arr;

    /**
     * @var int io次数统计
     */
    private static $io_step = 0;

    /**
     * 初始化
     */
    public static function init()
    {
        if (!isset($_GET['UIS_DEBUG_MODE']) || !self::envCheck()) {
            return;
        }
        self::initDebugMode((int)$_GET['UIS_DEBUG_MODE']);
        if (0 === self::$debug_mode) {
            return;
        }
        self::$start_time = microtime(true);
        //分离日志信息
        new LogRouter(LogHelper::getMainLogger());
    }

    /**
     * 运行环境检查
     * @return bool
     */
    private static function envCheck()
    {
        //只允许内网访问
        $ip = FFanIp::get();
        return FFanIp::isInternal($ip);
    }

    /**
     * 初始化调试参数
     * @param string $debug_mode
     */
    private static function initDebugMode($debug_mode)
    {
        //如果中间带,号，表示多种模式组合
        if (false !== strpos($debug_mode, ',')) {
            $modes = explode(',', $debug_mode);
            $debug_mode = 0;
            foreach ($modes as $mode_item) {
                if (empty($mode_item)) {
                    continue;
                }
                $debug_mode |= (int)trim($mode_item);
            }
        } else {
            $debug_mode = (int)$debug_mode;
        }
        //-1就打开所有的调试模式（除:args）
        if (-1 === $debug_mode) {
            $debug_mode = 0xffff ^ self::MODE_DEBUG_ARGS;
        } elseif (-2 === $debug_mode) {
            $debug_mode = 0xffff;
        }
        self::$debug_mode = $debug_mode;
    }

    /**
     * 是否开启 MODE_DEBUG_ERROR 模式
     * @return bool
     */
    public static function isDebugError()
    {
        return 0 !== (self::$debug_mode & self::MODE_DEBUG_ERROR);
    }

    /**
     * 是否开启 MODE_DEBUG_TRACE_ARGS 模式
     * @return bool
     */
    public static function isDebugTrace()
    {
        return 0 !== (self::$debug_mode & self::MODE_DEBUG_TRACE);
    }

    /**
     * 是否开启 MODE_DEBUG_ARGS 模式
     * @return bool
     */
    public static function isTraceArgs()
    {
        return 0 !== (self::$debug_mode & self::MODE_DEBUG_ARGS);
    }

    /**
     * 是否开启 MODE_DEBUG_IO
     * @return bool
     */
    public static function isDebugIO()
    {
        return 0 !== (self::$debug_mode & self::MODE_DEBUG_IO);
    }

    /**
     * 是否开启 MODE_DEBUG_EXCEPTION
     * @return bool
     */
    public static function isTraceException()
    {
        return 0 !== (self::$debug_mode & self::MODE_DEBUG_EXCEPTION);
    }

    /**
     * @param string $name
     * @return Console
     */
    public static function getConsole($name)
    {
        if (!isset(self::$console_arr[$name])) {
            self::$console_arr[$name] = new Console();
        }
        return self::$console_arr[$name];
    }

    /**
     * 生成代码回溯信息
     * @param array|null 代码回溯信息 ，如果为null，立即获取
     * @return string
     */
    public static function codeTrace($trace_list = null)
    {
        if (!is_array($trace_list)) {
            $trace_list = debug_backtrace();
            //第一条信息就是codeTrace，没意义
            array_shift($trace_list);
        }
        $array_format = array();
        $array_count = 0;
        $error_arr = array();
        //第一次循环，生成参数信息，做了一点优化，相同内容的数组或者对象已经打印过了，就不再打印
        for ($i = count($trace_list) - 1; $i >= 0; --$i) {
            $tmp_info = &$trace_list[$i];
            if (!isset($tmp_info['args'])) {
                $tmp_info['arg_info'] = '';
                continue;
            }
            //如果开启参数打印
            if (self::$debug_mode & self::MODE_DEBUG_ARGS) {
                $arg_info = '';
                foreach ($tmp_info['args'] as $arg_id => $each_arg) {
                    $param_type = gettype($each_arg);
                    $param_format = self::varFormat($each_arg, 4096);
                    if ('array' === $param_type || 'object' === $param_type) {
                        $md5_param = md5($param_format);
                        if (isset($array_format[$md5_param])) {
                            $param_format = '[...]';
                            $param_type = $array_format[$md5_param];
                        } else {
                            $arr_name = $param_type . '_' . $array_count;
                            $array_format[$md5_param] = $arr_name;
                            $param_type = $arr_name;
                            $array_count++;
                        }
                    }
                    $arg_info .= PHP_EOL . '[Arg_' . $arg_id . '] => (' . $param_type . ')' . $param_format;
                }
                $tmp_info['arg_info'] = $arg_info;
            }
        }
        $index = 0;
        foreach ($trace_list as $step_info) {
            $error_msg = '#' . $index++ . ' ';
            if (isset($step_info['file'])) {
                $error_msg .= $step_info['file'];
            }
            if (isset($step_info['line'])) {
                $error_msg .= '(line ' . $step_info['line'] . ') ';
            }
            if (isset($step_info['class'])) {
                $error_msg .= $step_info['class'];
            }
            if (isset($step_info['type'])) {
                $error_msg .= $step_info['type'];
            }
            if (isset($step_info['function'])) {
                $error_msg .= $step_info['function'] . '()';
            }
            if (isset($step_info['arg_info']) && (self::$debug_mode & self::MODE_DEBUG_ARGS)) {
                $error_msg .= $step_info['arg_info'] . PHP_EOL;
            }
            $error_arr[] = $error_msg;
        }
        return join(PHP_EOL, $error_arr) . PHP_EOL;
    }

    /**
     * 将php变更格式化输出（二进制安全！）
     * @param mixed $php_var 变更
     * @param int $str_cut_len 字符串截取长度
     * @return string
     */
    public static function varFormat($php_var, $str_cut_len = 1048576)
    {
        $type = gettype($php_var);
        switch ($type) {
            case 'boolean':
                $result_str = $php_var ? 'True' : 'False';
                break;
            case 'integer':
            case 'double':
                $result_str = (string)$php_var;
                break;
            case 'NULL':
                $result_str = 'NULL';
                break;
            case 'resource':
                $result_str = 'Resource:' . get_resource_type($php_var);
                break;
            case 'string':
                $result_str = self::strFormat($php_var, $str_cut_len);
                break;
            case 'object':
                $result_str = 'Class of "' . get_class($php_var) . '"';
                $result_str .= self::strFormat(print_r($php_var, true), $str_cut_len);
                break;
            case 'array':
            default:
                $result_str = self::strFormat(print_r($php_var, true), $str_cut_len);
                break;
        }
        return $result_str;
    }

    /**
     * 字符串检测，包含二进制
     * @param string $str 字符串
     * @param int $str_cut_len 字符串截取长度
     * @return string
     */
    private static function strFormat($str, $str_cut_len)
    {
        if (!FFanStr::isUtf8($str)) {
            $str = '[BINARY STRING]' . base64_encode($str);
        }
        if (strlen($str_cut_len) > $str_cut_len) {
            $str = substr($str, 0, $str_cut_len);
        }
        return $str;
    }

    /**
     * 输出所有的调试信息
     * @param mixed $data
     */
    public static function displayDebugMessage($data)
    {
        $end_time = microtime(true);
        $result_content = 'TotalTime:' . round(($end_time - self::$start_time) * 1000, 3) . 'ms' . PHP_EOL
            . 'Memory:' . Utils::sizeFormat(memory_get_usage()) . PHP_EOL
            . 'Result:' . PHP_EOL . self::varFormat($data);
        $view_tabs = array(
            'RESULT' => $result_content
        );
        /**
         * @var string $name
         * @var Console $console
         */
        foreach (self::$console_arr as $name => $console) {
            $tmp_msg = $console->dump();
            if (empty($tmp_msg)) {
                continue;
            }
            $view_tabs[$name] = $tmp_msg;
        }
        if (self::isDebugTrace()) {
            $view_tabs['CODE TRACE'] = self::codeTrace();
        }
        $view_tabs['SERVER'] = self::varFormat($_SERVER);
        $view_tabs['GET'] = self::varFormat($_GET);
        if (!empty($_POST)) {
            $view_tabs['POST'] = self::varFormat($_POST);
        }
        if (!empty($_COOKIE)) {
            $view_tabs['COOKIE'] = self::varFormat($_COOKIE);
        }
        if (!empty($_SESSION)) {
            $view_tabs['SESSION'] = self::varFormat($_SESSION);
        }
        if (!empty($_FILES)) {
            $view_tabs['FILES'] = self::varFormat($_SESSION);
        }
        //如果是fatal error，也修正为200，不然gateway 会显示 500页面
        http_response_code(200);
        require_once __DIR__ . '/View.php';
        console_debug_view_display($view_tabs);
        exit(0);
    }

    /**
     * 是否开启调试模式
     * @return bool
     */
    public static function isDebugMode()
    {
        return self::$debug_mode > 0;
    }

    /**
     * 开始计时
     */
    public static function timerStart()
    {
        self::$timer_arr[] = microtime(true);
    }

    /**
     * 计时器结束
     * @param int $precision 小数点后位数
     * @return string
     */
    public static function timerStop($precision = 2)
    {
        if (empty(self::$timer_arr)) {
            return '';
        }
        $beg_time = array_pop(self::$timer_arr);
        $end_time = microtime(true);
        $result = round(($end_time - $beg_time) * 1000, $precision) . 'ms';
        return $result;
    }

    /**
     * 记录错误消息
     * @param integer $code 错误级别
     * @param string $message 错误消息.
     * @param string $file 错误文件.
     * @param integer $line 错误行号.
     * @return string
     */
    public static function recordError($code, $message, $file, $line)
    {
        static $names = [
            E_COMPILE_ERROR => 'PHP Compile Error',
            E_COMPILE_WARNING => 'PHP Compile Warning',
            E_CORE_ERROR => 'PHP Core Error',
            E_CORE_WARNING => 'PHP Core Warning',
            E_DEPRECATED => 'PHP Deprecated Warning',
            E_ERROR => 'PHP Fatal Error',
            E_NOTICE => 'PHP Notice',
            E_PARSE => 'PHP Parse Error',
            E_RECOVERABLE_ERROR => 'PHP Recoverable Error',
            E_STRICT => 'PHP Strict Warning',
            E_USER_DEPRECATED => 'PHP User Deprecated Warning',
            E_USER_ERROR => 'PHP User Error',
            E_USER_NOTICE => 'PHP User Notice',
            E_USER_WARNING => 'PHP User Warning',
            E_WARNING => 'PHP Warning'
        ];
        $type_str = isset($names[$code]) ? $names[$code] : 'Error';
        $log_msg = '[ERROR]' . $type_str . ': ' . $message . ' in ' . $file . ' on line ' . $line . PHP_EOL;
        $trace_info = debug_backtrace();
        array_shift($trace_info);
        $log_msg .= self::codeTrace($trace_info);
        LogHelper::getMainLogger()->error($log_msg);
        return $log_msg;
    }

    /**
     * 异常信息记录
     * @param \Throwable $exception
     * @return string
     */
    public static function recordException(\Throwable $exception)
    {
        $exception_class = get_class($exception);
        $log_msg = array("\n============$exception_class===========");
        $log_msg[] = '#' . (string)$exception;
        $log_msg[] = '#file => ' . $exception->getFile() . ': line ' . $exception->getLine();
        $trace_info = $exception->getTrace();
        $total_eno = count($trace_info) - 1;
        foreach ($trace_info as $eno => $each_trace) {
            $tmp_step = PHP_EOL . '#Step ' . ($total_eno - $eno) . ' ';
            if (isset($each_trace['file'], $each_trace['line'])) {
                $tmp_step .= 'file:' . $each_trace['file'] . ' (line ' . $each_trace['line'] . ')';
            }
            $tmp_step .= PHP_EOL . 'function：';
            if (isset($each_trace['class'])) {
                $tmp_step .= $each_trace['class'] . '->';
            }
            $tmp_step .= $each_trace['function'] . '()';
            $log_msg[] = $tmp_step;
        }
        $content = '[EXCEPTION]' . join(PHP_EOL, $log_msg) . "\n=================================\n";
        LogHelper::getMainLogger()->error($content);
        return $content;
    }

    /**
     * 增加Io步数
     * @return string
     */
    public static function addIoStep()
    {
        ++self::$io_step;
        return self::getIoStepStr();
    }

    /**
     * 获取io次数(格式化好的字符串)
     * @return string
     */
    public static function getIoStepStr()
    {
        return '[I/O][#'. self::$io_step .']';
    }

    /**
     * 获取io步数
     * @return int
     */
    public static function getIoStep()
    {
        return self::$io_step;
    }

    /**
     * 在控制台输出调试信息
     * @param string $message 消息
     * @param string $tab 标签
     */
    public static function debug($message, $tab = self::IO_TAB_NAME)
    {
        self::getConsole($tab)->log($message);
    }
}
