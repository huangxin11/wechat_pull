<?php
/**
 * Created by PhpStorm.
 * User: 97371
 * Date: 2019/2/15
 * Time: 16:13
 */

namespace lib;


use app\index\model\AndPullModel;
use app\index\model\AritcleListsModel;
use think\Exception;

include_once "phpQuery.php";

class File
{
    private $arr;

    public function __construct($array)
    {
        $this->arr = $array;
    }

    public function saveContentUrl()
    {
        $arr = $this->arr;
        $model = new AndPullModel();
        logResult(json_encode($arr));
        $pattern = '/var msg_cdn_url = \"(.*?)\"/';
        while (count($arr) != 0) {
            set_time_limit(0);
            ini_set('memory_limit', '-1');
            $main = array_shift($arr);
            try {
                try {
                    $content = file_get_contents($main['url']);
                } catch (Exception $e) {
                    logResult($e->getMessage());
                    exit;
                }

                if ($content) {
                    preg_match($pattern, $content, $ms);
                    if (!$ms) {
                        $imgurl = '';
                    } else {
                        $imgurl = $ms[1];
                    }
                    $jiexi = \phpQuery::newDocument($content);
                    $length = pq("div[id=js_content]>p", $jiexi)->length;
                    $index = '';
                    for ($i = $length - 1; $i >= 0; --$i) {
                        if (pq("div[id=js_content]>p:eq($i):contains('相关阅读')", $jiexi)->length) {
                            $index = $i - 1;
                            break;
                        }
                    }
                    if (empty($index) && pq("div[id=js_content]>p:contains('相关阅读')", $jiexi)->length) {
                        $index = $length - 12;
                    }
                    if (!empty($index)) {
                        pq("div[id=js_content]>p:gt($index))", $jiexi)->remove();
                    }
                    $c = pq("div[id=js_content])", $jiexi)->html();
                    $model->addImgUrl($main['id'], $imgurl, trim(strip_tags($c)));
                } else {
                    if (!array_key_exists('count', $main)) {
                        $main['count'] = 1;
                    } else {
                        if ($main['count'] == 3) {
                            //存表
                            $model->add_failure_data($main['id'], $main['url']);
                        } else {
                            $main['count'] += 1;
                            array_push($arr, $main);
                        }
                    }
                    sleep(10);
                }
            } catch (Exception $e) {
                array_push($arr, $main);
                logResult('获取封面图报错日志：' . $e->getMessage() . 'AND' . $e->getTraceAsString());
                return false;
            }
        }
        return true;
    }

    //删除含有section标签相关阅读
    public function remove_section_reading()
    {
        $arr = $this->arr;
        $model = new AndPullModel();
        while (count($arr) != 0) {
            set_time_limit(0);
            $main = array_shift($arr);
            try {
                $jiexi = \phpQuery::newDocument($main['content']);
                $length = pq("section>p", $jiexi)->length;
                $index = '';
                for ($i = $length - 1; $i >= 0; --$i) {
                    if (pq("section>p:eq($i):contains('相关阅读')", $jiexi)->length) {
                        $index = $i - 1;
                        break;
                    }
                }
                if ($index) {
                    pq("section>p:gt($index))", $jiexi)->remove();
                }
                $c = pq($jiexi)->html();
                $model->update_article_content($main['id'], trim(addslashes($c)));
            } catch (Exception $e) {
                array_push($arr, $main);
                logResult('删除相关阅读出错B：' . $e->getMessage() . 'AND' . $e->getTraceAsString());
                sleep(180);
            }

        }
    }

}