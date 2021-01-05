<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helper\ErrorHelper;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\HttpServer\Router\Dispatched;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class YApiMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    public function __construct(ContainerInterface $container, RequestInterface $request, HttpResponse $response)
    {
        $this->container = $container;
        $this->request   = $request;
        $this->response  = $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var  $response */
        $response = $handler->handle($request);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $retArr   = json_decode($contents, true);
        if (empty($retArr[ErrorHelper::RET_DATA])) {
            return $response;
        }
        $data = $retArr[ErrorHelper::RET_DATA];

        $params = $this->request->all();
        if (isset($params['table'])) {
            $className = $params['table'];
        } else {
            $attr = $request->getAttributes();
            /** @var Dispatched $dispatched */
            $dispatched = $attr[Dispatched::class];
            if (!isset($dispatched) || !isset($dispatched->handler->callback)) {
                return $handler->handle($request);
            }
            $callback      = $dispatched->handler->callback;
            $callbackArr   = explode("\\", $callback);
            $controller    = array_pop($callbackArr);
            $controllerArr = explode("@", $controller);
            $controller    = $controllerArr[0];
            $className     = substr($controller, 0, strlen($controller) - 10);
            $className     = self::humpToLine($className);
        }
        $list = Db::select("SELECT `column_name`, `data_type`, `column_comment` FROM information_schema. COLUMNS WHERE `table_schema` = 'Y3-erp' AND `table_name` = '{$className}' ORDER BY ORDINAL_POSITION;");

        $commentList = [];
        /** @var \stdClass $commentObj */
        foreach ($list ?? [] as $commentObj) {
            $commentList[$commentObj->column_name] = $commentObj;
        }

        $properties = [];
        foreach ($data as $dKey => $datum) {
            if (is_array($datum)) {
                foreach ($datum as $key => $val) {
                    if (isset($properties[$key])) {
                        continue;
                    }
                    $type = 'string';
                    if (!isset($commentList[$key])) {
                        $properties[$key] = [
                            'type' => $type,
                        ];
                        continue;
                    }
                    /** @var \stdClass $commentObj */
                    $commentObj = $commentList[$key];
                    switch ($commentObj->data_type) {
                        case 'int':
                        case 'tinyint':
                        case 'bigint':
                            $type = 'number';
                            break;
                    }
                    // key以_at结尾，默认为时间格式
                    if (strpos($key, '_at') !== false) {
                        $type = 'string';
                    }
                    $properties[$key] = [
                        'type'        => $type,
                        'description' => $commentObj->column_comment,
                    ];
                }
            } else {
                if (isset($properties[$dKey])) {
                    continue;
                }
                $type = 'string';
                if (!isset($commentList[$dKey])) {
                    $properties[$dKey] = [
                        'type' => $type,
                    ];
                    continue;
                }
                /** @var \stdClass $commentObj */
                $commentObj = $commentList[$dKey];
                switch ($commentObj->data_type) {
                    case 'int':
                    case 'tinyint':
                    case 'bigint':
                        $type = 'number';
                        break;
                }
                // key以_at结尾，默认为时间格式
                if (strpos($dKey, '_at') !== false) {
                    $type = 'string';
                }
                $properties[$dKey] = [
                    'type'        => $type,
                    'description' => $commentObj->column_comment,
                ];
            }
        }
        $toFormatArr = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'type'       => 'object',
            'properties' => [
                'code'  => ['type' => 'number'],
                'msg'   => ['type' => 'string'],
                'total' => ['type' => 'number'],
                'data'  => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => $properties,
                        'required'   => [],
                    ]
                ],
            ]
        ];

        return $this->response->json($toFormatArr);
    }

    /**
     * 驼峰转下划线
     * @param string $str
     * @return string|string[]|null
     */
    private
    static function humpToLine($str)
    {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        $str = substr($str, 1, strlen($str) - 1);
        return $str;
    }
}