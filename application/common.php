<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * @param $url
 * @param $data
 * @param int $timeout
 * @return bool|string
 * post请求
 */
function post_curl($url, $data, $timeout = 5)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * @param $url
 * @param int $timeout
 * @return bool|string
 * get请求
 */

function get_curl($url, $timeout = 5)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/*curl操作
   *url---会话请求URL地址
   *method---请求方式，有POST和GET两种，默认get方式
   *res---返回数据类型，有json和array两种，默认返回json格式
   *data---POST请求时的参数，数组格式
   */
function curlRequest( $url, $method='get', $data=array()){

    //初始化一个会话操作
    $ch = curl_init();

    //设置会话操作的通用参数
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_URL , $url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //POST方式时参数设置
    if( $method == 'post' ) curl_setopt($ch, CURLOPT_POST, 1);

    if( !empty($data) ) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    //执行会话
    $data = curl_exec($ch);
    //关闭会话，释放资源
    curl_close($ch);

    //返回指定格式数据
    return $data;
}

/**
 * @return float
 * 获取毫秒数
 */
function getMillisecond()
{
    list($microsecond, $time) = explode(' ', microtime()); //' '中间是一个空格
    return (float)sprintf('%.0f', (floatval($microsecond) + floatval($time)) * 1000);
}
/**
构建日志文件，调用可以生成该方法使用日志，排错可用
 */
function logResult($word='') {
    $fp = fopen("/var/www/html/wechat_pull/log.txt","a+");
    flock($fp, LOCK_EX) ;
    fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}