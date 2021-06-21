<?php
/**
 * Created by PhpStorm.
 * User: 97371
 * Date: 2019/1/9
 * Time: 15:00
 */

namespace app\index\controller;


use app\index\model\AritcleListsModel;
use think\Config;
use think\Controller;
use think\Exception;
use think\Session;
use lib\File;
class Weixin extends Controller
{
    /**
     *拉取微信公众号素材
     */
    public function saveArticle(){
        $appid = 'wx5c3a871a1c03ba02';
        $secret = '1630327dd36f5d07f566597006611ced';
        $articleList = new AritcleListsModel();
        //accessToken
        $accessToken = $articleList->getAccess_token($appid,$secret);
        //var_dump($accessToken);exit;
        //素材总数
        $count = $articleList->get_countMaterial($accessToken['access_token']);
        //var_dump($count);exit;
        $for_num = ceil($count['news_count']/20);
        $i = 0;
        $result = '';
        while ($i<$for_num){
            set_time_limit(0);
            try{
                $material_list = $articleList->get_material_list($accessToken['access_token'],$i,20);
                if (!array_key_exists('errcode',$material_list)){
                    $item = $material_list['item'];
                    $articleList->save_article($item);
                    ++$i;
                }else{
                    sleep(120);
                }
                $result = true;
            }catch (Exception $e){
                $result = false;
                $articleList->logResult('拉取微信公众号报错：'.$e->getMessage().'++'.$e->getTraceAsString());
                echo 1;exit;
            }
        }
        return $result;
    }
    /**
     * 标签和文章的联系
     */
    public function tagArticle(){
        $appear_num = Config::get('appear_num');
        $articleList = new AritcleListsModel();
        try{
            //标签
            $tag_list = $articleList->getTags();
            //文章标题，内容，id
            $article_count = $articleList->getArticle_count();
            $for_num = ceil($article_count/20);
            for ($i = 0;$i<$for_num;++$i){
                set_time_limit(0);
                $article_list = $articleList->getArticle_list($i,20);
                foreach ($article_list as $item){
                    foreach ($tag_list as $list){
                        $tag_id = $articleList->check_TagArticle($item['id'],$list['id']);
                        $title_appear_num = substr_count($item['title'],$list['tag_name']);
                        $content_appear_num = substr_count($item['content'],$list['tag_name']);
                        //此处判断表中是否有该数据
                        if (empty($tag_id)){
                            if (strpos($item['title'],$list['tag_name'])){
                                //保存标签，文章关联表
                                try{
                                    $articleList->add_TagArticlt($item['id'],$list['id'],$title_appear_num,$content_appear_num);
                                }catch (Exception $e){
                                    $articleList->logResult("添加标签和文章的关联报错A:".$e->getMessage()."++".$e->getTraceAsString());
                                    echo 2;exit;
                                }
                            }else{
                                //if ($appear_num){
                                if ($content_appear_num >= $appear_num){
                                    //保存标签，文章关联表
                                    try{
                                        $articleList->add_TagArticlt($item['id'],$list['id'],$title_appear_num,$content_appear_num);
                                    }catch (Exception $e){
                                        $articleList->logResult("添加标签和文章的关联报错B:".$e->getMessage()."++".$e->getTraceAsString());
                                        echo 3;exit;
                                    }
                                }
                                /*}else{
                                     if ($content_appear_num >= 5){
                                         //保存标签，文章关联表
                                         try{
                                             $articleList->add_TagArticlt($item['id'],$list['id'],$title_appear_num,$content_appear_num);
                                         }catch (Exception $e){
                                             $articleList->logResult("添加标签和文章的关联报错c:".$e->getMessage()."++".$e->getTraceAsString());
                                         }
                                     }
                                 }*/
                            }
                        }else{
                            foreach ($tag_id as $id){
                                if ($title_appear_num != $id['title_count'] || $content_appear_num != $id['content_count']){
                                    try{
                                        $articleList->update_TagArticle($list['id'],$item['id'],$title_appear_num,$content_appear_num);
                                    }catch (Exception $e){
                                        $articleList->logResult("修改标签和文章的关联:".$e->getMessage()."++".$e->getTraceAsString());
                                        echo 4;exit;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            sleep(5);
            $result = true;
        }catch (Exception $e)
        {
            $result = false;
            $articleList->logResult('文章和标题的关联出错：'.$e->getMessage().'++'.$e->getTraceAsString());
            echo 5;exit;
        }
        return $result;
    }
    /**
     * 统计每个标签下得文章总数
     *
     */
    public function countTagArticle(){
        //获取所有标签得id
        $articleList = new AritcleListsModel();
        $TagIds = $articleList->getTagId();
        try{
            foreach ($TagIds as $v){
                //查询每一个标签下有多少文章
                $countNum = $articleList->counArticle($v['id']);
                //保存到标签表中
                $articleList->addCountArticle($v['id'],$countNum);
            }
            $result = true;
        }catch (Exception $e){
            $result = false;
            $articleList->logResult('统计标签下文章报错：'.$e->getMessage().'++'.$e->getTraceAsString());
            echo 6;exit;
        }
        return $result;

    }
    /**
     * 获取文章全部内容，封面图
     * */
    public function imgUrl(){
        //获取所有的文章url，id
        $articleList = new AritcleListsModel();
        $article_list = $articleList->getArticle_Url();
        $file = new File($article_list);
        try{
            $result = $file->saveContentUrl();
        }catch (Exception $e){
            $result = false;
            $articleList->logResult('获取封面图出错:'.$e->getMessage().'++'.$e->getTraceAsString());
            echo 7;exit;
        }
        return $result;
    }
    //如果有section标签得处理
    public function remove_reading(){
        $article = new AritcleListsModel();
        $article_count = $article->getArticle_count();
        $for_num = ceil($article_count/20);
        for ($i = 0;$i<$for_num;++$i){
            $article_list = $article->getArticleAll($i);
            $file = new File($article_list);
            try{
                $file->remove_section_reading();
            }catch (Exception $e){
                $article->logResult('删除相关阅读出错A:'.$e->getMessage().'++'.$e->getTraceAsString());
                echo 8;exit;
                return false;
            }
        }
        return true;
    }
    /**
     * 测试
     */
    public function test(){
        return "执行到我了";
        //var_dump(time());exit;
    }


    /**
     * 设置出现次数参数
     */
    public function setConfig($configValue='5'){
        Session::set('appear_num',$configValue);
    }

}