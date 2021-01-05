<?php

namespace App\Helper;

use GuzzleHttp\Exception\RequestException;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Http封装类
 * Class Http
 * @package App\Library
 */
class HttpHelper
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Hyperf\Guzzle\ClientFactory
     */
    private $clientFactory;

    public function __construct(LoggerFactory $loggerFactory, ClientFactory $clientFactory)
    {
        // 第一个参数对应日志的 name, 第二个参数对应 config/autoload/logger.php 内的 key
        $this->logger = $loggerFactory->get('guzzle', 'http');
        $this->client = $clientFactory->create();
    }

    /**
     * 请求Http请求
     * @param $url
     * @param $config
     * @param $isLog
     * @return \GuzzleHttp\Client
     * @throws \GuzzleHttp\GuzzleException
     */
    public function get(string $url, array $config = [], $isLog = false)
    {
        return $this->request('get', $url, $config, $isLog);
    }

    /**
     * @param string $url
     * @param array  $options
     * @param bool   $isLog
     * @return \GuzzleHttp\Client
     * @throws \GuzzleHttp\GuzzleException
     */
    public function post(string $url, array $config = [], $isLog = false)
    {
        return $this->request('post', $url, $config, $isLog);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array  $options
     * @param bool   $isLog
     * @return null|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\GuzzleException
     */
    public function request(string $method, string $url = '', array $config = [], $isLog = false)
    {
        $log         = '';
        $responseMsg = "";

        if ($isLog) {
            $log .= $url . " config:" . json_encode($config);
        }
        $response = null;
        try {
            $response = $this->client->request($method, $url, $config);
            if ($isLog) {
                $responseMsg = $response->getBody();
                $this->logger->info($log . " result:" . $responseMsg);
            }
        } catch (RequestException $e) {
            $responseMsg = $e->getMessage();
            $this->logger->error($log . " result:" . $responseMsg);
        }
        return $response;
    }
}