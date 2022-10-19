<?php
/**
 * @desc ExceptionHandler
 * @author Tinywan(ShaoBo Wan)
 * @date 2022/9/10 14:14
 */
declare(strict_types=1);

namespace tinywan;

use think\db\exception\DbException;
use think\exception\Handle as ThinkHandel;
use think\exception\InvalidArgumentException;
use think\exception\RouteNotFoundException;
use think\exception\ValidateException;
use think\facade\Config;
use think\facade\Env;
use think\facade\Log;
use think\Request;
use think\Response;
use Throwable;
use tinywan\event\NotifyEvent;
use tinywan\exception\BaseException;
use tinywan\exception\JWTRefreshTokenExpiredException;
use tinywan\exception\JWTTokenException;
use tinywan\exception\JWTTokenExpiredException;

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
     * @var int
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
        $this->config = array_merge($this->config, Config::get('exception', []));
        if (isset($this->config['ignore_report'])) {
            $this->ignoreReport = array_merge($this->ignoreReport, $this->config['ignore_report']);
        }
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
        $this->isDebugResponse($e);
        $this->triggerNotifyEvent($e);
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
            'ip' => $request->ip(),
            'is_mobile' => $request->isMobile(),
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
        $status = $this->config['status'];
        if ($e instanceof RouteNotFoundException) {
            $this->statusCode = $status['route'] ?? 404;
            $this->errorMessage = 'This Route Does Not Exist';
        } elseif ($e instanceof ValidateException) {
            $this->statusCode = $status['validate'] ?? 400;
            $this->errorMessage = $e->getMessage();
        } elseif ($e instanceof JWTTokenException || $e instanceof JWTTokenExpiredException) {
            $this->statusCode = 401;
            $this->errorMessage = $e->getMessage();
        } elseif ($e instanceof JWTRefreshTokenExpiredException) {
            $this->statusCode = 402;
            $this->errorMessage = $e->getMessage();
        } elseif ($e instanceof InvalidArgumentException) {
            $this->statusCode = $status['invalid_argument'] ?? 415;
            $this->errorMessage = 'config exception：' . $e->getMessage();
        } elseif ($e instanceof DbException) {
            $this->statusCode = 500;
            $this->errorMessage = 'database exception：'.$e->getMessage();
        } else {
            $this->statusCode = $status['server_error'] ?? 500;
            $this->errorMessage = 'Server Unknown Error';
            Log::error(array_merge($this->responseData, [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'error_file'    => $e->getFile(),
                'error_file_line'    => $e->getLine(),
            ]));
        }
    }

    /**
     * @desc 调试模式：错误处理器会显示异常以及详细的函数调用栈和源代码行数来帮助调试，将返回详细的异常信息。
     * @param Throwable $e
     * @return void
     */
    protected function isDebugResponse(Throwable $e): void
    {
        if (!empty(Env::get('app_debug'))) {
            $this->responseData['error_message'] = $this->errorMessage;
            $this->responseData['error_trace'] = explode("\n", $e->getTraceAsString());
            $this->responseData['file'] = $e->getFile();
            $this->responseData['line'] = $e->getLine();
        }
    }

    /**
     * @desc 触发通知事件
     * @param Throwable $e
     * @return void
     */
    protected function triggerNotifyEvent(Throwable $e): void
    {
        if ($this->config['trigger_event']['enable'] ?? false) {
            $responseData = $this->responseData;
            $responseData['message'] = $this->errorMessage;
            $responseData['file'] = $e->getFile();
            $responseData['line'] = $e->getLine();
            NotifyEvent::dingTalkRobot($responseData, $this->config);
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
        return Response::create($responseBody, 'json', $this->statusCode)->header($header);
    }
}
