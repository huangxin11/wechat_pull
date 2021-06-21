<?php
/**
 * Created by PhpStorm.
 * User: 97371
 * Date: 2019/1/9
 * Time: 14:41
 */
namespace app\index\model;
use think\Db;
use think\Exception;
use think\Model;
class AritcleListsModel extends Model{
    /*curl操作
   *url---会话请求URL地址
   *method---请求方式，有POST和GET两种，默认get方式
   *res---返回数据类型，有json和array两种，默认返回json格式
   *data---POST请求时的参数，数组格式
   */
    public function curlRequest( $url, $method='get', $data=array()){

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

    //获取ACCESS_TOKEN
    public function getAccess_token($appid,$secret){
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        $result = $this->curlRequest($url);
        return json_decode($result,true);
    }
    //获取素材总数
    public function get_countMaterial($access_token){
        $url = "https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token={$access_token}";
        $count = $this->curlRequest($url);
        return json_decode($count,true);
    }
    //获取素材列表
    public function get_material_list($access_token,$offset = 0,$count = 20){
        $offset = $offset*20;
        $data['type'] = 'news';
        $data['offset'] = (int)$offset;
        $data['count'] = $count;
        $url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token={$access_token}";
        $result = $this->curlRequest($url,'post',json_encode($data));
        return json_decode($result,true);
    }
    //获取永久素材
    public function get_material($access_token,$data){
        $url = "https://api.weixin.qq.com/cgi-bin/material/get_material?access_token={$access_token}";
        $result = $this->curlRequest($url,'post',json_encode($data));
        return json_decode($result,true);
    }
    //获取所有的标签
    public function getTags(){
        $sql = "SELECT id,tag_name FROM planet_applet.`al_tag_last` where is_del = 1";
        $result = Db::query($sql);
        return $result;
    }
    //检测tag-aritcle表中是否有重复
    public function check_TagArticle($article_id,$tag_id){
        $sql = "select title_count,content_count from planet_applet.al_article_tag where article_id = {$article_id} AND tag_id = {$tag_id}";
        $tag_id = Db::query($sql);
        return $tag_id;
    }
    //添加文章
    //htmlspecialchars
    //htmlentities
    //htmlspecialchars_decode
    public function add_article($data){
        $db = db('al_article_lists');
        try {
            $result = $db->insert(['media_id'=>$data['media_id'],'title'=>$data['title'],'author'=>$data['author'],'digest'=>$data['digest'],'content_source_url'=>$data['content_source_url'],'url'=>$data['url'],'update_time'=>$data['update_time'],'create_time'=>$data['create_time'],'show_cover_pic'=>$data['show_cover_pic'],'thumb_media_id'=>$data['thumb_media_id']]);
        }catch (Exception $e){
         $this->logResult($e->getMessage());
        }

        //$sql = "INSERT INTO `al_article_lists` (media_id,title,author,digest,content_source_url,url,update_time,create_time,show_cover_pic,thumb_media_id) SELECT '{$data['media_id']}','{$data['title']}','{$data['author']}','{$data['digest']}','{$data['content_source_url']}','{$data['url']}',{$data['update_time']},{$data['create_time']},{$data['show_cover_pic']},'{$data['thumb_media_id']}' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM `al_article_lists` WHERE url='{$data['url']}' AND media_id='{$data['media_id']}')";
        //$result = Db::execute($sql);
        return $result;
    }
    //判断数据表中是否有该文章
    public function check_media_id($media_id,$thumb_media_id){
        $sql = "select media_id,update_time,url from planet_applet.al_article_lists where media_id ='{$media_id}' and thumb_media_id ='{$thumb_media_id}'";
        $result = Db::query($sql);
        return $result;
    }
    //修改文章
    public function update_article($data){
        $sql = "update planet_applet.al_article_lists set title='{$data['title']}',author='{$data['author']}',digest='{$data['digest']}',content_source_url='{$data['content_source_url']}',url='{$data['url']}',update_time={$data['update_time']},create_time={$data['create_time']},show_cover_pic={$data['show_cover_pic']},thumb_media_id='{$data['thumb_media_id']}'";
        Db::execute($sql);
    }
    //修改文章内容
    public function update_article_content($id,$content){
        $time = time();
        $sql = "UPDATE planet_applet.al_article_lists SET content = '{$content}',update_time={$time} WHERE id = {$id}";
        Db::execute($sql);
    }
    //保存获取的素材
    public function save_article($item){
        try{
            Db::startTrans();
            Db::execute('SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');
            $data = array();
            foreach ($item as $k=>$v){
                $data['media_id'] = $v['media_id'];
                $data['create_time'] = $v['content']['create_time'];
                $data['update_time'] = $v['content']['update_time'];
                if (count($v['content']['news_item']) == 1){
                    foreach ($v['content']['news_item'] as $key=>$val){
                        $data['title'] = $val['title'];
                        $data['author'] = $val['author'];
                        $data['digest'] = $val['digest'];
                        //$data['content'] = $val['content'];
                        $data['url'] = $val['url'];
                        $data['content_source_url'] = $val['content_source_url'];
                        $data['show_cover_pic'] = $val['show_cover_pic'];
                        $data['thumb_media_id'] = $val['thumb_media_id'];
                    };
                    //判断是否存在文章
                    $is_exits = $this->check_media_id($data['media_id'],$data['thumb_media_id']);
                    if (count($is_exits) == 3){
                        var_dump($is_exits);exit;
                        if ($is_exits['update_time'] != $data['update_time']){
                            try{
                                $this->update_article($data);
                            }catch (Exception $e){
                                $this->logResult("修改拉取数据报错：".$e->getMessage().'++'.$e->getTraceAsString());
                                echo 9;exit;
                            }
                        }
                    }else{
                        try{
                            $this->add_article($data);
                        }catch (Exception $e){
                            $this->logResult("错误数据：".$data);
                            $this->logResult("添加拉取数据报错：".$e->getMessage().'++'.$e->getTraceAsString());
                            echo 10;exit;
                        }
                    }


                }else{
                    foreach ($v['content']['news_item'] as $key=>$val){
                        $data['title'] = $val['title'];
                        $data['author'] = $val['author'];
                        $data['digest'] = $val['digest'];
                        //$data['content'] = $val['content'];
                        $data['url'] = $val['url'];
                        $data['content_source_url']= $val['content_source_url'];
                        $data['show_cover_pic'] = $val['show_cover_pic'];
                        $data['thumb_media_id'] = $val['thumb_media_id'];
                        //判断是否存在文章
                        $is_exits = $this->check_media_id($data['media_id'],$data['thumb_media_id']);
                        if (count($is_exits) == 3){
                            if ($is_exits['update_time'] != $data['update_time']){
                                try{
                                    $this->update_article($data);
                                }catch (Exception $e){
                                    $this->logResult("修改拉取数据报错：".$e->getMessage().'++'.$e->getTraceAsString());
                                    echo 11;exit;
                                }
                            }
                        }else{
                            try{
                                $this->add_article($data);
                            }catch (Exception $e){
                                $this->logResult("添加拉取数据报错：".$e->getMessage().'++'.$e->getTraceAsString());
                                echo 12;exit;
                            }
                        }
                    }
                }
            }
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            //$error = $e->getMessage()."\n".$e->getTraceAsString();
            $this->logResult("执行拉取任务出错：".$e->getMessage());
            echo 13;exit;
        }

    }

    //获取素材总数
    public function getArticle_count(){
        $sql = "select count(*) from planet_applet.al_article_lists where is_update = 0";
        $count = Db::query($sql);
        return $count[0]['count(*)'];
    }
    //获取所有素材的标题和内容
    public function getArticle_list($page,$pageSize = 20){
        $page = $page*20;
        $sql = "select id,media_id,title,content from planet_applet.al_article_lists limit {$page},{$pageSize}";
        $result = Db::query($sql);
        /*foreach ($result as $k=>$v){
            $result[$k]['content'] = htmlspecialchars_decode(stripslashes($v['content']));
        }*/
        return $result;
    }
    //获取文章表中得所有数据
    public function getArticleAll($page,$pageSize = 20){
        $page = $page*20;
        $sql = "select id,content from planet_applet.al_article_lists where is_update = 0 LIMIT {$page},{$pageSize}";
        $article_all = Db::query($sql);
        return $article_all;
    }
    //添加文章标签关联表
    public function add_TagArticlt($article_id,$tag_id,$title_count,$content_count){
        $add_time = time();
        $sql = "insert into planet_applet.al_article_tag (article_id,tag_id,add_time,title_count,content_count) value ($article_id,$tag_id,$add_time,$title_count,$content_count)";
        Db::query($sql);
    }
    //修改文章标签关联表
    public function update_TagArticle($tag_id,$article_id,$title_count,$content_count){
        $update_time = time();
        $sql = "update planet_applet.al_article_tag set title_count = '{$title_count}',content_count = '$content_count',update_time = '{$update_time}' where tag_id = '{$tag_id}' AND article_id = '{$article_id}'";
        Db::execute($sql);
    }
    //获取所有标签得id
    public function getTagId(){
        $sql = "SELECT id FROM planet_applet.al_tag";
        $tag_ids = Db::query($sql);
        return $tag_ids;
    }
    //查询当前标签下文章得总数
    public function counArticle($tag_id){
        $sql = "SELECT COUNT(*) FROM al_tag_relation atr LEFT JOIN al_article_tag aat ON atr.`last_id` = aat.`tag_id` WHERE atr.`parent_id` = {$tag_id}";
        $countNum = Db::query($sql);
        return $countNum[0]['COUNT(*)'];
    }
    //添加文章数到标签表中
    public function addCountArticle($tag_id,$countNum = 0){
        $sql = "UPDATE planet_applet.`al_tag` SET article_count = {$countNum} WHERE id = {$tag_id}";
        $id = Db::execute($sql);
        return $id;
    }
    //获取文章的链接
    public function getArticle_Url(){
        $sql = "SELECT id,url FROM planet_applet.`al_article_lists` where is_update = 1";
        $url = Db::query($sql);
        return $url;
    }
    //修改文章表，添加分封面图
    public function addImgUrl($id,$img_url,$content){
        $sql = "UPDATE planet_applet.`al_article_lists` SET img_url = '{$img_url}',content = '{$content}',is_update = 0 WHERE id = {$id}";
        $result = Db::execute($sql);
        return $result;
    }
    //获取封面图失败的保存在表中
    public function add_failure_data($article_id,$url){
        $sql = "insert into planet_applet.al_failure_data (article_id,url) VALUE ({$article_id},'{$url}')";
        $id = Db::execute($sql);
        return $id;
    }
    /**
    构建日志文件，调用可以生成该方法使用日志，排错可用
     */
    function logResult($word='') {
        $fp = fopen("d:\\log\\log.txt","a");
        flock($fp, LOCK_EX) ;
        fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    /**
     * 测试
     */
    public function test(){
        $sql = "select id from planet_applet.al_article_lists";
        $result = Db::query($sql);
        return $result;
    }

}