<?php

namespace FFan\Std\Console;

/**
 * Class Console
 * @package FFan\Std\Console
 */
class Console
{
    /**
     * @var array
     */
    private $msg_buffer;

    /**
     * @var string 连接字符串
     */
    private $join_str;

    /**
     * XapiDebugLog constructor.
     * @param string $join_str
     */
    public function __construct($join_str = PHP_EOL)
    {
        $this->join_str = $join_str;
    }

    /**
     * 记录日志消息
     * @param string $content
     */
    public function log($content)
    {
        $this->msg_buffer[] = $content;
    }

    /**
     * 输出日志内容
     * @return string
     */
    public function dump()
    {
        if (empty($this->msg_buffer)) {
            return '';
        }
        $result = join($this->join_str, $this->msg_buffer) . $this->join_str;
        $this->msg_buffer = null;
        return $result;
    }
}
