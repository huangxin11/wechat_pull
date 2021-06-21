<?php


namespace app\index\model;


use think\Model;

class indexModel extends Model
{
    public function curl_action($url, $data, $type = 'post')
    {
        if ($type == 'post') {
            return post_curl($url, $data);
        } else {
            return get_curl($url, $data);
        }
    }
    public function getMillisecond(){
        return getMillisecond();
    }

}