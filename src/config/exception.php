<?php
/**
 * @desc 异常配置文件
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 * @date 2022/9/11 15:56
 */

return [
    // 开关
    'enable' => true,
    // 不需要记录错误日志
    'ignore_report' => [
        \tinywan\exception\BadRequestHttpException::class,
        \tinywan\exception\UnauthorizedHttpException::class,
        \tinywan\exception\ForbiddenHttpException::class,
        \tinywan\exception\NotFoundHttpException::class,
        \tinywan\exception\TooManyRequestsHttpException::class,
        \tinywan\exception\ServerErrorHttpException::class,
        \think\exception\RouteNotFoundException::class,
    ],
    // 自定义HTTP状态码
    'status' => [
        'route' => 404, // 路由异常
        'validate' => 400, // 验证器异常
        'jwt_token' => 401, // JWT 认证失败
        'jwt_token_expired' => 402, // JWT 令牌过期
        'server_error' => 500, // 服务器内部错误
    ],
    // 自定义响应消息
    'body' => [
        'code' => 0,
        'msg' => '服务器内部异常',
        'data' => [],
    ],
    // 事件
    'trigger_event' => [
        // 是否开启通知时间
        'enable' => false,
        // 钉钉机器人
        'dingtalk' => [
            'accessToken' => 'xxxxxxxxxxxxxxxx',
            'secret' => 'xxxxxxxxxxxxxxxx',
            'title' => '钉钉机器人异常通知',
        ],
        // 邮件
        'email' => [
            'accessToken' => 'xxxxxxxxxxxxxxxx',
            'secret' => 'xxxxxxxxxxxxxxxx',
            'title' => '邮件异常通知',
        ],
        'is_trace' => false,
    ],
];
