<?php

declare(strict_types=1);

namespace Chive\Command;

use Chive\Annotation\ClassRoute;
use Chive\Annotation\MethodRoute;
use Chive\Annotation\WebsocketRoute;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\Annotation\AnnotationCollector;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class RoutesCommand extends HyperfCommand
{
    /** @var array 生成路由需要忽略掉的方法 */
    static $ignoreMehtod = [
        '__construct',
        'success',
        'fail',
        '__proxyCall',
        '__getParamsMap',
        'handleAround',
        'makePipeline',
        'getClassesAspects',
        'getAnnotationAspects',
        '__handlePropertyHandler',
        '__handle',
    ];

    /** @var string 路由文件路径 */
    protected $createPath = 'config/routes.php';

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('chive:route');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        $this->line('开始生成路由文件...', 'info');
        $routeArr     = $this->readClassRoute();
        $httpStr      = $this->createHttpRouteFileString($routeArr);
        $websocketStr = $this->createWebsocketRouteFileString();

        file_put_contents($this->createPath, $httpStr . $websocketStr . $this->extra());
        $this->line('生成路由完成', 'info');
    }

    /**
     * 额外补充写入信息
     * @return string
     */
    public function extra()
    {
        return '';
    }

    /**
     * 解析类路由
     */
    public function readClassRoute()
    {
        $classList = AnnotationCollector::getClassesByAnnotation(ClassRoute::class);
        ksort($classList);

        $routeArr = [];
        /**
         * @var string     $className
         * @var ClassRoute $classRoute
         */
        foreach ($classList as $className => $classRoute) {
            $obj     = new \ReflectionClass($className);
            $methods = $obj->getMethods();

            // 服务端
            $server = 'http';
            if (!empty($classRoute->server)) {
                $server = $classRoute->server;
            }
            if (!isset($routeArr[$server])) {
                $routeArr[$server] = [];
            }

            // 路径
            $prefix = '';
            if (!empty($classRoute->prefix)) {
                $prefix = $classRoute->prefix;
            } else {
                $classArr = explode("\\", $className);
                if ($classArr[0] != 'App' && $classArr[1] != 'Controller') {
                    $this->line('不能生成app\Controller\目录外的路由：' . $className, 'error');
                    continue;
                }
                $pathArr = [];
                foreach ($classArr as $pathName) {
                    if (in_array($pathName, ['App', 'Controller'])) {
                        continue;
                    }
                    $pathName = lcfirst($pathName);
                    if (strlen($pathName) == 2) {
                        $pathName = strtolower($pathName);
                    }
                    if (strpos($pathName, 'Controller') !== false) {
                        $pathName = substr($pathName, 0, strlen($pathName) - 10);
                    }
                    $pathArr[] = $pathName;
                }
                $prefix = '/' . implode("/", $pathArr);
            }
            if ($prefix != '/') {
                $prefix = $prefix . '/';
            }

            // 请求方法, 默认只有POST
            if (empty($classRoute->methods)) {
                $method = ['post'];
            } else {
                $method = explode(",", $classRoute->methods);
            }
            foreach ($method as &$m) {
                $m = strtoupper($m);
            }

            // 中间件
            $classMiddleware = '';
            if (!empty($classRoute->middleware)) {
                $classMiddleware = $classRoute->middleware;
                if (strpos($classMiddleware, 'App\\Middleware') === false) {
                    $classMiddleware = 'App\\Middleware\\' . $classMiddleware;
                }
            }

            $function = [];
            /** @var \stdClass $stdClass */
            foreach ($methods as $stdClass) {
                if (in_array($stdClass->name, self::$ignoreMehtod)) {
                    continue;
                }
                $funcName         = $stdClass->name;
                $methodMiddleware = '';
                $funcMethod       = $method;
                // 读取方法注解
                $methodsAnnotation = AnnotationCollector::getClassMethodAnnotation($className, $funcName);
                /** @var MethodRoute $annotation */
                foreach ($methodsAnnotation ?? [] as $annotationName => $annotation) {
                    if ($annotationName == MethodRoute::class) {
                        if (!empty($annotation->path)) {
                            $funcName = $annotation->path;
                        }
                        if (!empty($annotation->middleware)) {
                            $methodMiddleware = $annotation->middleware;
                            if (strpos($methodMiddleware, 'App\\Middleware') === false) {
                                $methodMiddleware = 'App\\Middleware\\' . $methodMiddleware;
                            }
                        }
                        if (!empty($annotation->methods)) {
                            $funcMethod = explode(",", $annotation->methods);
                            foreach ($funcMethod as &$m) {
                                $m = strtoupper($m);
                            }
                        }
                        break;
                    }
                }
                $function[] = [
                    'funcName'   => $funcName,          // 访问路由名
                    'method'     => $funcMethod,        // 可访问方法
                    'controller' => $className,
                    'func'       => $stdClass->name,    // 函数名
                    'middleware' => $methodMiddleware,  // 当前方法使用的中间件
                ];
            }

            if (empty($function)) {
                continue;
            }
            $routeArr[$server][] = [
                'prefix'     => $prefix,
                'function'   => $function,
                'middleware' => $classMiddleware,       // 当前group用的中间件
            ];
        }
        return $routeArr;
    }

    /**
     * 创建路由文件
     * @param $routeArr
     */
    public function createHttpRouteFileString($routeArr)
    {
        $header = "<?php
declare(strict_types=1);

/**
 * 路由文件自动生成，请勿手动修改
 * 生成方法：
 * 1.在controller中写注解ClassRoute/MethodRoute
 * 2.运行 php bin/hyperf.php chive:route 生成路由文件
 */
 
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');
Router::get('/favicon.ico', function () {
    return '';
});
Router::get('/info', function () {
    return [
        'APP_NAME'    => env('APP_NAME'),
        'APP_ENV'     => env('APP_ENV'),
        'SERVER_DATE' => date('Y-m-d H:i:s', time()),
        'SERVER_TIME' => time(),
    ];
});

";
        $str    = '';
        foreach ($routeArr as $server => $groupList) {
            $t = '';
            if ($server != 'http') {
                $str .= "Router::addServer('{$server}', function () {" . PHP_EOL;
                $t   = "\t";
            }
            foreach ($groupList as $group) {
                $str .= $t . "Router::addGroup('{$group['prefix']}', function () {" . PHP_EOL;
                foreach ($group['function'] as $route) {
                    $str .= $t . "\t" . "Router::addRoute([";
                    foreach ($route['method'] as &$method) {
                        $method = "'" . $method . "'";
                    }
                    $str .= implode(",", $route['method']);
                    $str .= "], '{$route['funcName']}', [{$route['controller']}::class, '{$route['func']}']";
                    if (!empty($route['middleware'])) {
                        $str .= ", ['middleware' => [{$route['middleware']}::class]]";
                    }
                    $str .= ");" . PHP_EOL;
                }
                if (!empty($group['middleware'])) {
                    $str .= $t . "}, ['middleware' => [{$group['middleware']}::class]]);" . PHP_EOL;
                } else {
                    $str .= $t . "});" . PHP_EOL;
                }
                if ($server == 'http') {
                    $str .= PHP_EOL;
                }
            }
            if ($server != 'http') {
                $str .= "});" . PHP_EOL;
            }
        }

        return $header . $str . PHP_EOL;
    }

    /**
     * 读websocket路由配置
     */
    public function createWebsocketRouteFileString()
    {
        $classList = AnnotationCollector::getClassesByAnnotation(WebsocketRoute::class);
        if (empty($classList)) {
            return '';
        }
        $str = '';
        /**
         * @var string         $className
         * @var WebsocketRoute $websocketRoute
         */
        foreach ($classList as $className => $websocketRoute) {
            $str .= "Router::addServer('{$websocketRoute->server}', function () {
    Router::get('/', '{$className}');
});" . PHP_EOL;
        }
        return $str;
    }

}
