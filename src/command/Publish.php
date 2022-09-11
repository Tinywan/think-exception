<?php
/**
 * @desc Publish
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 * @date 2022/9/11 15:56
 */

declare(strict_types=1);

namespace tinywan\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Publish extends Command
{
    /**
     * 配置指令
     */
    protected function configure()
    {
        $this->setName('tinywan:exception')->setDescription('Publish Exception Handler');
    }

    /**
     * 执行指令
     * @param Input  $input
     * @param Output $output
     * @return null|int
     * @see setCode()
     */
    protected function execute(Input $input, Output $output): ?int
    {
        if (!file_exists(config_path().'exception.php')) {
            copy(__DIR__.'/../config/exception.php', config_path().'exception.php');
        }
        return 1;
    }
}
