<?php


namespace app\index\controller;


use app\index\model\AndPullModel;
use think\Controller;
use think\Exception;
use think\File;

class AndPull extends Controller
{
    private $appid = 'wx5c3a871a1c03ba02';
    private $secret = '1630327dd36f5d07f566597006611ced';

    public function saveArticle()
    {
        logResult('进入拉取状态!');
        $model = new AndPullModel();
        $accessToken = $model->getAccessToken($this->appid, $this->secret);
        logResult($accessToken);
        if (array_key_exists('errcode', $accessToken)) {
            $str = '状态码:' . $accessToken['errcode'] . '++报错信息:' . $accessToken['errmsg'];
            switch ($accessToken['errcode']) {
                case '-1':               //系统繁忙
                    logResult($str);
                    sleep(10);
                    $accessToken = $model->getAccessToken($this->appid, $this->secret);
                    break;
                case '89507':           //一个小时内被禁止调用
                    logResult($str);
                    sleep(3600);
                    $accessToken = $model->getAccessToken($this->appid, $this->secret);
                    break;
                default:
                    logResult($str);
                    break;
            }
        }
        if (array_key_exists('access_token', $accessToken)) {
            //var_dump($accessToken);
            //获取素材总数
            $count = $model->getCountMaterial($accessToken['access_token']);
            //var_dump($count);
            $for_num = ceil($count['news_count'] / 20);
            $i = 0;
            //获取列表素材
            while ($i < $for_num) {
                set_time_limit(0);
                ini_set('memory_limit', '-1');
                try {
                    $material_list = $model->getMaterialList($accessToken['access_token'], $i, 20);
                    if (!array_key_exists('errcode', $material_list)) {
                        $item = $material_list['item'];
                        $model->saveArticle($item);
                        $i++;
                    } else {
                        sleep(120);
                    }
                } catch (Exception $e) {
                    logResult('拉取微信公众号报错：' . $e->getMessage() . '++' . $e->getTraceAsString());
                    return false;
                }

            }

        }
        return true;
    }

    /**
     * 获取文章全部内容，封面图
     * */
    public function imgUrl()
    {
        //获取所有的文章url，id
        $model = new AndPullModel();
        $count = $model->getCountArticle();
        if (count($count[0]['total_num'])) {
            $num = ceil($count[0]['total_num'] / 20);
            $i = 0;
            while ($i < $num) {
                set_time_limit(0);
                ini_set('memory_limit', '-1');
                $article_list = $model->getArticleUrl($i);
                $file = new \lib\File($article_list);
                try {
                    $result = $file->saveContentUrl();
                    $i++;
                } catch (Exception $e) {
                    $result = false;
                    logResult('获取封面图出错:' . $e->getMessage() . '++' . $e->getTraceAsString());
                }
            }
        } else {
            $result = true;
            logResult('没有需要修改的文章');
        }
        $countNum = $model->getCountArticle();
        if ($countNum['0']['total_num']) {
            $this->imgUrl();
        } else {
            logResult('文章内容获取成功');
            return $result;
        }

    }

}