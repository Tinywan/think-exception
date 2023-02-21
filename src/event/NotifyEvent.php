<?php
/**
 * @desc NotifyEvent
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 * @date 2022/9/11 15:35
 */

declare(strict_types=1);

namespace tinywan\event;

class NotifyEvent
{
    /**
     * 发送钉钉机器人
     * @param array $args
     * @param array $config
     * @param string $name
     * @return bool|string
     */
    public static function dingTalkRobot(array $args, array $config, string $name = '')
    {
        $config = $config['trigger_event']['dingtalk'];
        $accessToken = $config['accessToken'];
        $secret = $config['secret'];
        $title = $config['title'];
        $message = ' - <font color="#dd00dd">监控来源： ' .$title. "</font> \n";
        if (!empty($name)) {
            $title = $name;
            $message = ' - <font color="#dd0000">监控来源： ' .$title. "</font> \n";
        }
        $message .= ' - 响应错误： ' .$args['message']. " \n";
        $message .= ' - 响应错误： ' .$args['message']. " \n";
        $message .= ' - 详细错误：' . $args['error'] . " \n";
        $message .= ' - 请求路由：' . $args['url'] . " \n";
        $message .= ' - 请求IP：' . $args['ip'] . " \n";
        $message .= ' - 请求时间：' . $args['timestamp'] . " \n";
        $message .= ' - 请求参数：' . json_encode($args['param']) . " \n";
        $message .= ' - 请求域名：' . $args['domain'] . " \n";
        $message .= ' - 异常文件：' . $args['file'] . " \n";
        $message .= ' - 异常文件行数：' . $args['line'] . " \n";
        $data = [
            'msgtype' => 'markdown',
            'markdown' => [
                'title' => $title,
                'text' => $message,
            ],
            'at' => [
                'isAtAll' => true,
            ],
        ];
        $orderPayUrl = 'https://oapi.dingtalk.com/robot/send?access_token=' . $accessToken;
        return  self::request_by_curl(self::_sign($orderPayUrl, $secret), json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @desc: 发送html内容
     * @param array $jsonArr
     * @return string
     * @author Tinywan(ShaoBo Wan)
     */
    public static function sendHtmlContent(array $jsonArr): string
    {
        $errorMessage = $jsonArr[0];
        $errorData = $jsonArr[1];
        $env = '开发环境';
        if (!app()->runningInConsole()) {
            $message = '<strong>请求站点：</strong> 默认 <br/>';
            if ($errorData['sub_domain'] == 'api') {
                $env = '正式环境';
            } elseif ($errorData['sub_domain'] == 'pre-api') {
                $env = '预发布环境';
            } elseif ($errorData['sub_domain'] == 'api-test') {
                $env = '测试环境';
            }
        } else {
            $message = '<strong>CLI 命令行异常：</strong>' . $errorMessage. ' <br/>';
        }
        $message .= '<strong>请求域名：</strong>' . $errorData['sub_domain']. ' <br/>';
        $message .= '<strong>请求时间：</strong>' . $errorData['timestamp']. ' <br/>';
        $message .= '<strong>请求路由：</strong>' . $errorData['request'] . ' <br/>';
        $message .= '<strong>请求端IP：</strong>' . $errorData['client']  . ' <br/>';
        $message .= '<strong>请求参数：</strong>' . json_encode($errorData['param']) . ' <br/>';
        $message .= '<strong>异常消息：</strong>' . $errorData['error_message'] . ' <br/>';
        $message .= '<strong>异常文件：</strong>' . $errorData['error_file'] . ' <br/>';
        $message .= '<strong>异常文件行数：</strong>' . $errorData['error_file_line'] . ' <br/>';
        $message .= '<strong>异常详细信息：</strong> <p>' . $errorData['error_trace'].'</p>';
        return send_email(config('email.aliyun')['email'], '[报警] <创培-'.$env.'-马兰花> '.$errorData['url'].' '.$errorMessage, $message, 'resty tinywan');
    }

    /**
     * @desc: 请求签名
     * @param string $url
     * @param string $secret
     * @return string
     * @author Tinywan(ShaoBo Wan)
     */
    private static function _sign(string $url, string $secret): string
    {
        [$s1, $s2] = explode(' ', microtime());
        $timestamp = (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
        $data = $timestamp . "\n" . $secret;
        $signStr = base64_encode(hash_hmac('sha256', $data, $secret, true));
        $signStr = utf8_encode(urlencode($signStr));
        return $url . "&timestamp=$timestamp&sign=$signStr";
    }

    /**
     * @desc: 自定义请求类
     * @param string $remote_server
     * @param string $postString
     * @return bool|string
     * @author Tinywan(ShaoBo Wan)
     */
    private static function request_by_curl(string $remote_server, string $postString)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json;charset=utf-8']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
