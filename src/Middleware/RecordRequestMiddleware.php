<?php
/**
 * Class RecordRequestMiddleware
 * 作者: su
 * 时间: 2020/11/26 16:56
 * 备注: 记录接口响应时间
 */

namespace Chive\Middleware;


use Chive\Helper\CommonHelper;
use Chive\Helper\LogHelper;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LogLevel;

class RecordRequestMiddleware implements MiddlewareInterface
{
	/**
	 * @var RequestInterface
	 */
	protected $request;

	public function __construct(RequestInterface $request)
	{
		$this->request = $request;
	}

	/**
	 * Process an incoming server request.
	 *
	 * Processes an incoming server request in order to produce a response.
	 * If unable to produce the response itself, it may delegate to the provided
	 * request handler to do so.
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$start_time = CommonHelper::getMicrosecond();
		$attr       = $request->getAttributes();
		/** @var Dispatched $dispatched */
		$dispatched = $attr[Dispatched::class];
		if (!isset($dispatched) || !isset($dispatched->handler->route)) {
			return $handler->handle($request);
		}
		$http_route = $dispatched->handler->route;
		Context::set('http_start_time', $start_time);
		Context::set('http_route', $http_route);
		context::set('http_use_memory', memory_get_usage());
		context::set('http_params', $this->request->all());
		$response = $handler->handle($request);
		self::writeLog();
		return $response;
	}

	/**
	 * @param \Throwable|null $throw
	 */
	public static function writeLog(\Throwable $throw = null)
	{
		if (env('RECORD_REQUEST', false) == false) {
			return;
		}
		$start_time  = Context::get('http_start_time');
		$http_route  = Context::get('http_route');
		$start_money = context::get('http_use_memory');
		$params      = context::get('http_params');
		$params      = !empty($params) ? '&' . http_build_query($params) : '';
		$use_time    = (CommonHelper::getMicrosecond() - $start_time) / 1000;
		$use_money   = memory_get_usage() - $start_money;
		$use_money   = CommonHelper::getFilesize($use_money);
		if ($throw) {
			LogHelper::info("[{$use_time}][{$use_money}] " . $http_route . $params . " throw [{$throw->getCode()}]{$throw->getMessage()}",
				LogLevel::WARNING, LogHelper::Group_Http, 0);
		} else {
			LogHelper::info("[{$use_time}][{$use_money}] " . $http_route . $params, LogLevel::INFO, LogHelper::Group_Http, 0);
		}
	}
}