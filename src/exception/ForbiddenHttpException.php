<?php
/**
 * @desc 访问接口资源授权（authorization）：指允许访问某一个资源的权限
 *
 * @see https://tools.ietf.org/html/rfc7231#section-6.5.3
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 * @date 2022/3/6 14:14
 */
declare(strict_types=1);

namespace tinywan\exception;

/**
 * ForbiddenHttpException represents a "Forbidden" HTTP exception with status code 403.
 * Use this exception when a user is not allowed to perform the requested action. Using different credentials might or might not allow performing the requested action. If you do not want to expose authorization information to the user, it is valid to respond with a 404 yii\web\NotFoundHttpException.
 */
class ForbiddenHttpException extends BaseException
{
    /**
     * @var int
     */
    public $statusCode = 403;

    /**
     * @link 解决跨域问题
     * @var array
     */
    public $header = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Allow-Headers' => 'Authorization,Content-Type,If-Match,If-Modified-Since,If-None-Match,If-Unmodified-Since,X-Requested-With,Origin',
        'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS',
    ];

    /**
     * @var string
     */
    public $errorMessage = '对不起，您没有该接口访问权限，请联系管理员';
}
