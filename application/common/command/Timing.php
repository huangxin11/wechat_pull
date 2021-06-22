<?php
/**
 * Created by PhpStorm.
 * User: 97371
 * Date: 2019/4/15
 * Time: 16:30
 */

namespace app\common\command;


use think\console\Command;
use think\Exception;
use think\console\Output;
use think\console\Input;

class Timing extends Command
{
    protected function configure()
    {
        $this->setName('Timing')->setDescription("每天拉取数据");
    }

    /**
     * 定时拉取后台数据
     */
    protected function execute(Input $input, Output $output)
    {
        logResult('测试timing');exit;
        logResult('进入定时拉取文章任务' . "\n");
        $this->index();

        /*** 这里写计划任务列表集 END ***/
        logResult('结束定时拉取文章任务' . "\n");
    }

    public function index()
    {
        try {
            sleep(0);
            logResult('微信拉取数据开始' . "\n");
            $material = action('index/and_pull/saveArticle');
            logResult('微信拉取数据结束' . "\n");
            if ($material) {
                logResult('获取封面图开始' . "\n");
                $img_url = action('index/andpull/imgUrl');
                logResult('获取封面图结束' . "\n");
                if (!$img_url) {
                    logResult('获取封面图失败' . "\n");
                }
            } else {
                logResult('定时任务拉取素材失败' . "\n");
            }

        } catch (Exception $e) {
            logResult('定时任务执行失败:' . $e->getMessage() . '++' . $e->getTraceAsString() . "\n");
        }
    }

}