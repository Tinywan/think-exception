<?php
/**
 * @desc 请求不存在异常类
 *
 * @see https://tools.ietf.org/html/rfc7231#section-6.5.3
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 * @date 2022/3/6 14:14
 */

declare(strict_types=1);

namespace tinywan\exception;

class NotFoundHttpException extends BaseException
{
    /**
     * @var int
     */
    public $statusCode = 404;

    /**
     * @var string
     */
    public $errorMessage = '未找到请求的资源';
}
