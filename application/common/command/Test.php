<?php


namespace app\common\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;

class Test extends Command
{
    protected function configure()
    {
        $this->setName('test')->setDescription("Here is the remark");
    }

    protected function execute(Input $input, Output $output)
    {
        logResult('执行了Test定时任务');
        action('index/index/hello');
        //action('index/and_pull/save_article');
        $output->writeln("TestCommand");
    }

}