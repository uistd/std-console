<?php

/**
 * 输出显示
 * @param array $data_tabs
 */
function console_debug_view_display($data_tabs)
{
    $tab_html = '';
    $content_html = '';
    $index = 0;
    foreach ($data_tabs as $name => $content) {
        if (is_array($content)) {
            $content = \FFan\Std\Console\Debug::varFormat($content);
        }
        $tab_html .= '<button class="console_tab_item console_tab_nav" data-index=' . $index . '>' . $name . '</button>' . PHP_EOL;
        $content_html .= '<div class="console_tab_item console_tab_content" data-index="' . $index . '"><textarea spellcheck="false" readonly class="console_result_area">' . $content . '</textarea></div>' . PHP_EOL;
        $index++;
    }
    $uri = $_SERVER['REQUEST_URI'];
    $pos = strpos($uri, '?');
    if (false !== $pos) {
        $uri = substr($uri, 0, $pos);
    }
    $uri .= '?UIS_DEBUG_MODE=-1000';
    $post_url = $_SERVER['HTTP_HOST'].':'. $_SERVER['SERVER_PORT']. $uri;
    $tab_html .= '<button class="console_tab_item console_tab_nav" data-index=' . $index . '>EVAL</button>';
    $content_html .= '<div class="console_tab_item console_tab_content console_eval_div" data-index="'. $index .'">'.
        '<textarea id="console_eval_code">echo "Hello console";</textarea>'.
        '<p><button id="console_eval_run_btn"> RUN </button></p>' .
        '<pre id="console_eval_result"></pre></div>';
    echo <<< eod
        <!doctype html>
        <html lang="en">
        <meta content="text/html; charset=utf-8" http-equiv="content-type"/>
        <head><meta charset="UTF-8"><title>consoleDebug</title></head>
        <style>
        html, .console_result_area{
            background-color: #eeeeee;
        }
        button.active {
            color:green;
            font-weight: bold;
        }
        .console_tab_content{
            display:none;
            padding:20px;
        }
        .console_tab_content.active{
            display:block;
        }
        .console_result_area{
            font-size:14px;
            line-height: 150%;
        }
        .console_eval_div {
            text-align:center;
        }
        .console_eval_div textarea{
            width:80%;
            height:240px;
            margin:0 auto;
            border:1px solid green;
        }
        #console_eval_run_btn{
            font-size:16px;
            font-weight:bold;
        }
        #console_eval_result{
            text-align:left;
            width:80%;
            margin:0 auto;
        }
        </style>
        <body data-uri="http://$post_url">
        <script src="https://admin.ffan.com/Public/js/jquery-2.1.0.min.js"></script>
        <script>
        $(function(){
            var window_height = $(window).height() - 100;
            var window_width = $(window).width() - 60;
            $('.console_result_area').height(window_height).width(window_width);
            var all_tabs = $('.console_tab_nav');
            var all_items = $('.console_tab_item');
            all_tabs.click(function(){
                var active_index = $(this).data('index');
                all_items.each(function(index, item){
                    var jq_item = $(item);
                    if (active_index === jq_item.data('index')) {
                        jq_item.addClass('active');
                    } else {
                        jq_item.removeClass('active');
                    }
                });
            }).first().trigger('click');
            $('#console_eval_run_btn').click(function(){
                var php_code = $('#console_eval_code').val();
                $('#console_eval_result').html('LOADING...');
                var post_url = $(document.body).data('uri');
                $.post(post_url, {code:php_code}, function(result){
                    $('#console_eval_result').html(result);
                });
            });
        });
        </script>
eod;
    echo '<div>', $tab_html, '</div>', '<div>', $content_html, '</div>', '</body></html>';
}