<?php
/**
 * Class create
 * 作者: su
 * 时间: 2020/11/16 16:43
 * 备注: 快速创建文件
 */

// 参数类
class ParasParser
{
    public static  $options = "a:m:c:d:";
    private static $help    = <<<EOF

  帮助信息:
  Usage: /path/to/php create.php [options] -- [args...]

  -c            [必填]创建文件类名
  -a            [必填]作者名
  -m            [必填]备注mark
  -d            [可选]controller文件生成目录
  -h            help信息
  
  例：
  php create.php -c Test -m 类作用注释 -a 作者名


EOF;
    private static $error   = <<<EOF

  【必填项】
  -c            [必填]创建文件类名
  -a            [必填]作者名
  -m            [必填]备注mark

EOF;

    /**
     * 解析帮助参数
     * @param $opt
     */
    public static function params_h($opt)
    {
        if (empty($opt) || isset($opt["h"])) {
            die(self::$help);
        }
    }

    /**
     * 验证参数
     * @param $opt
     */
    public static function verify($opt)
    {
        if (empty($opt) || !isset($opt["c"]) || !isset($opt["a"]) || !isset($opt["m"])) {
            die(self::$error);
        }
    }
}

class Main
{
    public static $daoDir        = 'app/Dao';
    public static $requestDir    = 'app/Request';
    public static $serviceDir    = 'app/Service';
    public static $controllerDir = 'app/Controller';
    public static $routesPath    = 'config/routes.php';

    public static function run()
    {
        $opt = getopt(ParasParser::$options);
        ParasParser::params_h($opt);
        ParasParser::verify($opt);
        $author    = $opt['a'];
        $className = ucfirst($opt['c']);
        $mark      = $opt['m'];
        $date      = date('Y-m-d');
        $dir       = '';
        // controller变更生成目录
        $controllerDir = self::$controllerDir;
        if (isset($opt['d'])) {
            $dir           = ucfirst($opt['d']);
            $controllerDir = $controllerDir . '/' . $dir;
        }

        self::createDao(self::$daoDir, $className, $author, $mark, $date);
        self::createController($controllerDir, $className, $author, $mark, $date, $dir);
        self::createRequest(self::$requestDir, $className, $author, $mark, $date);
        self::createService(self::$serviceDir, $className, $author, $mark, $date);
        self::appendRoutes(self::$routesPath, $className, $mark, $dir);
    }

    public static function createDao($dir, $className, $author, $mark, $date)
    {
        $content = '<?php
/**
 * Class ' . $className . 'Dao
 * 作者: ' . $author . '
 * 时间: ' . $date . '
 * 备注: ' . $mark . '
 */

namespace App\Dao;

use App\Model\\' . $className . ';
use App\Model\Casts\TimeCasts;

class ' . $className . 'Dao  extends AbstractDao
{
    // model类名
    protected $modelClass = ' . $className . '::class;

    // 转格式字段
    protected $withCasts = [
        \'created_at\' => TimeCasts::class,
        \'updated_at\' => TimeCasts::class,
    ];

    // 主键key
    protected $primaryKey = \'id\';
    

}
';
        self::mkdirs($dir);
        $fileName = $dir . '/' . $className . 'Dao.php';
        self::writeFile($fileName, $content);
    }


    public static function createController($dir, $className, $author, $mark, $date, $lowerDir = '')
    {
        $content = '<?php
/**
 * Class ' . $className . 'Controller
 * 作者: ' . $author . '
 * 时间: ' . $date . '
 * 备注: ' . $mark . '
 */

namespace App\Controller';
        if (!empty($lowerDir)) {
            $content .= '\\' . $lowerDir;
        }
        $content .= ';
';
        if(!empty($lowerDir)) {
            $content .= '
use App\Controller\AbstractController;';
        }
        $content .='
use App\Request\\' . $className . 'Request;
use App\Service\\' . $className . 'Service;
use Hyperf\Di\Annotation\Inject;

class ' . $className . 'Controller extends AbstractController
{
    /**
     * @Inject()
     * @var ' . $className . 'Service
     */
    private $' . lcfirst($className) . 'Service;

    /**
     * 列表
     */
    public function getList()
    {
        $params = $this->request->all();
        $params = $this->verifyHelper->check($params, ' . $className . 'Request::LIST_RULE);
        $list   = $this->' . lcfirst($className) . 'Service->getList($params);
        return $this->success($list);
    }
    
    /**
     * 添加
     */
    public function add()
    {
        $params = $this->request->all();
        $params = $this->verifyHelper->check($params, ' . $className . 'Request::ADD_RULE);
        $this->' . lcfirst($className) . 'Service->add($params);
        return $this->success();
    }

    /**
     * 获取单条详情
     */
    public function getOne()
    {
        $params = $this->request->all();
        $params = $this->verifyHelper->check($params, ' . $className . 'Request::ONE_RULE);
        $list   = $this->' . lcfirst($className) . 'Service->getOne($params);
        return $this->success($list);
    }


}';

        self::mkdirs($dir);
        $fileName = $dir . '/' . $className . 'Controller.php';
        self::writeFile($fileName, $content);
    }

    public static function createRequest($dir, $className, $author, $mark, $date)
    {
        $content = '<?php
/**
 * Class ' . $className . 'Request
 * 作者: ' . $author . '
 * 时间: ' . $date . '
 * 备注: ' . $mark . '
 */

namespace App\Request;

class ' . $className . 'Request
{
    // 列表
    const LIST_RULE = [
        \'page_size\' => \'required|integer\',
        \'page\'      => \'required|integer\',
    ];

    // 添加
    const ADD_RULE = [

    ];

    // 单条详情
    const ONE_RULE = [
        \'id\' => \'required|integer\',
    ];

    const MESSAGE = [
        
    ];

}';
        self::mkdirs($dir);
        $fileName = $dir . '/' . $className . 'Request.php';
        self::writeFile($fileName, $content);
    }

    public static function createService($dir, $className, $author, $mark, $date)
    {
        $content = '<?php
/**
 * Class ' . $className . 'Service
 * 作者: ' . $author . '
 * 时间: ' . $date . '
 * 备注: ' . $mark . '
 */

namespace App\Service;


use App\Dao\\' . $className . 'Dao;
use Hyperf\Di\Annotation\Inject;

class ' . $className . 'Service extends AbstractService
{
    /**
     * @Inject()
     * @var ' . $className . 'Dao
     */
    protected $dao;
    
    
}';
        self::mkdirs($dir);
        $fileName = $dir . '/' . $className . 'Service.php';
        self::writeFile($fileName, $content);
    }

    public static function appendRoutes($fileName, $className, $mark, $dir = '')
    {
        $content = "
// {$mark}
Router::addGroup('/";
        if (!empty($dir)) {
            $content .= lcfirst($dir) . "/";
        }
        $content .= lcfirst($className) . "/',function (){
    Router::addRoute(['POST','OPTIONS'],'getList','App\Controller\\" . $className . "Controller@getList');//列表
    Router::addRoute(['POST','OPTIONS'],'add','App\Controller\\" . $className . "Controller@add');//添加
    Router::addRoute(['POST','OPTIONS'],'getOne','App\Controller\\" . $className . "Controller@getOne');//详情
});";


        file_put_contents($fileName, $content, FILE_APPEND);
        echo "追加路由完成" . PHP_EOL;
    }


    /**
     * 创建文件夹
     * @param     $dir
     * @param int $mode
     * @return bool
     */
    public static function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
        if (!self::mkdirs(dirname($dir), $mode)) return FALSE;
        return @mkdir($dir, $mode);
    }

    /**
     * 写文件
     * @param      $fileName
     * @param      $content
     * @param bool $append
     */
    public static function writeFile($fileName, $content)
    {
        if (file_exists($fileName) == true) {
            echo "【{$fileName}】文件已存在，请手动删除" . PHP_EOL;
            return;
        }
        $res = file_put_contents($fileName, $content);
        if ($res) {
            echo "写入【{$fileName}】完成" . PHP_EOL;
        } else {
            echo "写入【{$fileName}】失败！！！" . PHP_EOL;
        }
    }
}

Main::run();