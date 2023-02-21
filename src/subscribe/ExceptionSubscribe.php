<?php
/**
 * @desc 异常订阅，主要处理Log::error()产生的异常日志
 * @author Tinywan(ShaoBo Wan)
 * @date 2023/2/21 21:51
 */
declare(strict_types=1);

namespace tinywan\subscribe;

use think\facade\Config;
use think\facade\Env;
use tinywan\event\NotifyEvent;

class ExceptionSubscribe
{
    /**
     * 订阅 日志write方法
     * @param $args
     * @return void
     */
    public function onLogWrite($args)
    {
        if (isset($args->log['error'])) {
            if (empty(Env::get('app_debug'))) {
                $config = Config::get('exception', []);
                $tmpArgs = $args->log['error'];
                $newArgs = [
                    'message' => $tmpArgs[0],
                    'error' => $tmpArgs[0],
                    'url' => request()->url(),
                    'ip' => request()->ip(),
                    'domain' => request()->domain(),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'param' => request()->param(),
                    'file' => '手动记录错误日志触发',
                    'line' => 0,
                ];
                NotifyEvent::dingTalkRobot($newArgs, $config);
            }
        }
    }
}
