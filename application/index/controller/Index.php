<?php

namespace app\index\controller;

use app\index\model\indexModel;
use think\cache\driver\Redis;
use think\Exception;

class Index
{
    public function index()
    {
    }

    public function hello($name = 'ThinkPHP5')
    {
        logResult('执行到我了');
        var_dump(1);exit;
        header("Content-type: text/html; charset=utf-8");
        ini_set('soap.wsdl_cache_enabled','0');
        $url = "http://www.kmzhyf.cn:8085/zyf/ws/IThirdOrderWSDL?wsdl";
        $model = new indexModel();
        $company_num = "11196";
        $key = $model->getMillisecond();
        $new_company_pas = md5('x8j2kJc98JX89cu23hzaj');
        $psw = strtolower($new_company_pas);
        $sign = strtoupper(md5('updateAccountInfo' . $key . $psw));
        //var_dump($sign);exit;
        $params = [
            'company_num' => $company_num,
            'sign' => $sign,
            'key' => $key,
            'new_company_pas' => $new_company_pas
        ];
        $str = <<<here
<?xml version="1.0" encoding="UTF-8" ?>
<orderInfo>
    <head>
        <company_num>{$company_num}</company_num>
        <key>{$key}</key>
        <sign>{$sign}</sign>
        <new_company_pass>
            {$new_company_pas}
        </new_company_pass>
    </head>
</orderInfo>
here;
        //var_dump(htmlentities($str));exit;
$parStr = <<<str
<?xml version="1.0" encoding="UTF-8" ?><orderInfo>
<head>
    <company_num>11196</company_num>
    <key>1618393764140</key>
    <sign>43205F01ECE4E265398D8C8774EF75E4</sign>
    <new_company_pass>19f7c7039ad31c966a1d9f5f2bbf9a11</new_company_pass>
    </head>
</orderInfo>
str;


        try {
            // var_dump(htmlentities($str));exit;
            $client = new \SoapClient($url,array('trace'=>true));
            $result = $client->updateAccountInfo(htmlentities($parStr));
        }catch (Exception $e){
            var_dump($e->getMessage());exit;
        }

        var_dump($result);
        exit;
    }
}
