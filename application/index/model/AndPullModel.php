<?php


namespace app\index\model;


use think\Db;
use think\Exception;
use think\Model;

class AndPullModel extends Model
{
    //获取access_token
    public function getAccessToken($appid, $secret)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        $result = curlRequest($url);
        return json_decode($result, true);
    }

    //获取素材总数
    public function getCountMaterial($accessToken)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token={$accessToken}";
        $count = curlRequest($url);
        return json_decode($count, true);
    }

    //获取素材列表
    public function getMaterialList($access_token, $offset = 0, $count = 20)
    {
        $offset = $offset * 20;
        $data['type'] = 'news';
        $data['offset'] = (int)$offset;
        $data['count'] = $count;
        $url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token={$access_token}";
        $result = curlRequest($url, 'post', json_encode($data));
        return json_decode($result, true);
    }

    //判断文章是否已经存在
    public function checkMediaId($media_id, $thumb_media_id)
    {
        $sql = "select media_id,update_time,url from planet_applet.al_article_lists where media_id ='{$media_id}' and thumb_media_id ='{$thumb_media_id}'";
        $result = Db::query($sql);
        return $result;
    }

    //修改文章
    public function updateArticle($data)
    {
        $sql = "update planet_applet.al_article_lists set title='{$data['title']}',author='{$data['author']}',digest='{$data['digest']}',content_source_url='{$data['content_source_url']}',url='{$data['url']}',update_time={$data['update_time']},create_time={$data['create_time']},show_cover_pic={$data['show_cover_pic']},thumb_media_id='{$data['thumb_media_id']}'";
        Db::execute($sql);
    }

    //添加数据
    public function addArticle($data)
    {
//        $db = db('al_article_lists');
//        try {
//            $result = $db->insert(['media_id' => $data['media_id'], 'title' => $data['title'], 'author' => $data['author'], 'digest' => $data['digest'], 'content_source_url' => $data['content_source_url'], 'url' => $data['url'], 'update_time' => $data['update_time'], 'create_time' => $data['create_time'], 'show_cover_pic' => $data['show_cover_pic'], 'thumb_media_id' => $data['thumb_media_id']]);
//        }catch (Exception $e){
//            logResult($e->getMessage());
//            logResult(json_encode($data));
//        }
        $sql = "INSERT INTO `al_article_lists` (media_id,title,author,digest,content_source_url,url,update_time,create_time,show_cover_pic,thumb_media_id) SELECT '{$data['media_id']}','{$data['title']}','{$data['author']}','{$data['digest']}','{$data['content_source_url']}','{$data['url']}',{$data['update_time']},{$data['create_time']},{$data['show_cover_pic']},'{$data['thumb_media_id']}' FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM `al_article_lists` WHERE url='{$data['url']}' AND media_id='{$data['media_id']}')";
        $result = Db::execute($sql);
        return $result;
    }

    //获取文章列表总数
    public function getCountArticle()
    {
//        $sql = "SELECT COUNT(*) as total_num  FROM `al_article_lists` WHERE is_update = 1";
        $result = Db::table('al_article_lists')->where('is_update','1')->count();
//        $result = Db::query($sql);
        return $result;
    }

    //获取文章的链接
    public function getArticleUrl($offset)
    {
        $offset = $offset * 20;
        $sql = "SELECT id,url FROM `al_article_lists` where is_update = 1 limit {$offset},20";
        $url = Db::query($sql);
        return $url;
    }

    //修改文章表，添加分封面图
    public function addImgUrl($id, $imgurl, $content)
    {
        Db::startTrans();
        try {
            $db = db('al_article_lists');
            $result = $db->where('id', $id)->update(['content' => $content, 'img_url' => $imgurl, 'is_update' => 0]);
            //$sql = "UPDATE planet_applet.`al_article_lists` SET img_url = '{$img_url}',content = '{$content}',is_update = 0 WHERE id = {$id}";
            //$result = Db::execute($sql);
            Db::commit();
        } catch (Exception $e) {
            //logResult('失败的sql：' . $sql);
            Db::rollback();
            logResult('sql语句执行失败:' . $e->getMessage());
        }

        return $result;
    }

    //获取封面图失败的保存在表中
    public function add_failure_data($article_id, $url)
    {
        $sql = "insert into planet_applet.al_failure_data (article_id,url) VALUE ({$article_id},'{$url}')";
        $id = Db::execute($sql);
        return $id;
    }

    //修改文章内容
    public function update_article_content($id, $content)
    {
        $time = time();
        $sql = "UPDATE planet_applet.al_article_lists SET content = '{$content}',update_time={$time} WHERE id = {$id}";
        Db::execute($sql);
    }

    //保存获取的素材
    public function saveArticle($item)
    {
        try {
            Db::startTrans();
            Db::execute('SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');
            $data = [];
            foreach ($item as $k => $v) {
                $data['media_id'] = $v['media_id'];
                $data['create_time'] = $v['content']['create_time'];
                $data['update_time'] = $v['content']['update_time'];
                if (count($v['content']['news_item']) == 1) {
                    foreach ($v['content']['news_item'] as $key => $val) {
                        $data['title'] = addslashes($val['title']);
                        $data['author'] = $val['author'];
                        $data['digest'] = $val['digest'];
                        //$data['content'] = htmlentities($val['content']);
                        $data['url'] = $val['url'];
                        $data['content_source_url'] = $val['content_source_url'];
                        $data['show_cover_pic'] = $val['show_cover_pic'];
                        $data['thumb_media_id'] = $val['thumb_media_id'];
                    };
                    //判断是否存在文章
                    $is_exits = $this->checkMediaId($data['media_id'], $data['thumb_media_id']);
                    if (count($is_exits) == 3) {
                        logResult($is_exits);
                        if ($is_exits['update_time'] != $data['update_time']) {
                            try {
                                $this->updateArticle($data);
                            } catch (Exception $e) {
                                logResult("修改拉取数据报错：" . $e->getMessage() . '++' . $e->getTraceAsString());
                                exit;
                            }
                        }
                    } else {
                        try {
                            $this->addArticle($data);
                        } catch (Exception $e) {
                            logResult("错误数据：" . json_encode($data));
                            logResult("添加拉取数据报错：" . $e->getMessage() . '++' . $e->getTraceAsString());
                            exit;
                        }
                    }


                } else {
                    foreach ($v['content']['news_item'] as $key => $val) {
                        $data['title'] = addslashes($val['title']);
                        $data['author'] = $val['author'];
                        $data['digest'] = $val['digest'];
                        //$data['content'] = htmlentities($val['content']);
                        $data['url'] = $val['url'];
                        $data['content_source_url'] = $val['content_source_url'];
                        $data['show_cover_pic'] = $val['show_cover_pic'];
                        $data['thumb_media_id'] = $val['thumb_media_id'];
                        //判断是否存在文章
                        $is_exits = $this->checkMediaId($data['media_id'], $data['thumb_media_id']);
                        if (count($is_exits) == 3) {
                            if ($is_exits['update_time'] != $data['update_time']) {
                                try {
                                    $this->updateArticle($data);
                                } catch (Exception $e) {
                                    logResult("修改拉取数据报错：" . $e->getMessage() . '++' . $e->getTraceAsString());
                                    exit;
                                }
                            }
                        } else {
                            try {
                                $this->addArticle($data);
                            } catch (Exception $e) {
                                logResult("添加拉取数据报错：" . $e->getMessage() . '++' . $e->getTraceAsString());
                                exit;
                            }
                        }
                    }
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            logResult("执行拉取任务出错：" . $e->getMessage());
            exit;
        }
    }
}