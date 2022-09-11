<?php
/**
 * @desc ExceptionHandler
 * @author Tinywan(ShaoBo Wan)
 * @date 2022/9/10 14:14
 */
declare(strict_types=1);

namespace tinywan;

use think\exception\Handle as ThinkHandel;
use think\exception\InvalidArgumentException;
use think\exception\RouteNotFoundException;
use think\exception\ValidateException;
use think\facade\Log;
use think\Request;
use think\Response;
use Throwable;
use tinywan\exception\BaseException;

class Handler extends ThinkHandel
{
    /**
     * 不需要记录错误日志.
     *
     * @var string[]
     */
    public $dontReport = [];

    /**
     * HTTP Response Status Code.
     *
     * @var array
     */
    public $statusCode = 200;

    /**
     * HTTP Response Header.
     *
     * @var array
     */
    public $header = [];

    /**
     * Business Error code.
     *
     * @var int
     */
    public $errorCode = 0;

    /**
     * Business Error message.
     *
     * @var string
     */
    public $errorMessage = 'no error';

    /**
     * 响应结果数据.
     *
     * @var array
     */
    protected $responseData = [];

    /**
     * config下的配置.
     *
     * @var array
     */
    protected $config = [];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    /**
     * @access public
     * @desc Render an exception into an HTTP response.
     * @param Request $request
     * @param Throwable $e
     * @author Tinywan(ShaoBo Wan)
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        $this->addRequestInfoToResponse($request);
        $this->handlerAllException($e);
        return $this->buildResponse();
    }

    /**
     * @desc 请求的相关信息.
     * @param Request $request
     * @author Tinywan(ShaoBo Wan)
     * @return void
     */
    protected function addRequestInfoToResponse(Request $request): void
    {
        $this->responseData = array_merge($this->responseData, [
            'domain' => $request->domain(),
            'url' => $request->url(),
            'method' => $request->method(),
            'param' => $request->param(),
            'host' => $request->host(),
            'client' => $request->ip(),
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @desc 处理所有异常数据
     * @param Throwable $e
     * @author Tinywan(ShaoBo Wan)
     */
    protected function handlerAllException(Throwable $e)
    {
        if ($e instanceof BaseException) {
            $this->statusCode = $e->statusCode;
            $this->header = $e->header;
            $this->errorCode = $e->errorCode;
            $this->errorMessage = $e->errorMessage;
            if (isset($e->data)) {
                $this->responseData = array_merge($this->responseData, $e->data);
            }
            return;
        }
        $this->handlerExtraException($e);
    }

    /**
     * @desc 处理扩展的异常.
     * @param Throwable $e
     * @author Tinywan(ShaoBo Wan)
     */
    protected function handlerExtraException(Throwable $e): void
    {
        if ($e instanceof RouteNotFoundException) {
            $this->statusCode = 404;
            $this->errorMessage = '接口路由不存在';
        } elseif ($e instanceof ValidateException) {
            $this->statusCode = 415;
            $this->errorMessage = $e->getMessage();
        } elseif ($e instanceof InvalidArgumentException) { // 当参数不是预期的类型时
            $this->statusCode = 415;
            $this->errorMessage = '预期参数配置异常：' . $e->getMessage();
        } else {
            $this->statusCode = 500;
            $this->errorMessage = '请求失败，请稍后再试';
            $this->errorCode = 500;
            Log::error(array_merge($this->responseData, [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'error_file'    => $e->getFile(),
                'error_file_line'    => $e->getLine(),
            ]));
        }
    }

    /**
     * @desc 构造 Response.
     * @return Response
     */
    protected function buildResponse(): Response
    {
        $responseBody = [
            'code' => $this->errorCode,
            'msg' => $this->errorMessage,
            'data' => $this->responseData,
        ];

        $header = array_merge(['Content-Type' => 'application/json;charset=utf-8'], $this->header);
        return Response::create($responseBody, 'jsonp', $this->statusCode)->header($header);
    }
}
