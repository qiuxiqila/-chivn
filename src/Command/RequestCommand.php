<?php
/**
 * su.2021-1-19
 * 生成Yapi文档入参输入json格式
 */
declare(strict_types=1);

namespace Chive\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;
use Throwable;

/**
 * @Command
 */
class RequestCommand extends HyperfCommand
{
	/** @var string 生成目录 */
	protected $createPath = 'runtime/request/';
	/** @var string request目录 */
	protected $requestPath = 'app/Request/';
	// 处理文件分割符
	const Split_Str = '^G';


	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;

		parent::__construct('chive:request');
	}

	public function configure()
	{
		parent::configure();
		$this->setDescription('自动生成Yapi入参格式json，生成在【' . $this->createPath . '】');
	}

	public function handle()
	{
		$this->line('开始生成request json文件...', 'info');
		$files = self::getFiles($this->requestPath);
		if (empty($files)) {
			die("没有文件");
		}

		foreach ($files as $fileName) {
			$fileArr = explode("/", $fileName);

			$name = array_pop($fileArr);            // 文件名
			$path = implode("/", $fileArr) . '/';   // 对应路径

			$list           = self::decodeFile($path, $name);
			$createFileName = str_replace(".php", ".txt", $name);
			self::createFile($this->createPath, $createFileName, $list);
		}
		$this->line("生成完成，请查看目录：" . $this->createPath, 'info');
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
	 * 获取目录下包含的文件列表
	 * @param $path
	 * @return array
	 */
	public static function getFiles($path)
	{
		$fileList = [];
		if (is_dir($path)) {
			$dir = scandir($path);
			foreach ($dir as $value) {
				$sub_path = $path . $value;
				if ($value == '.' || $value == '..') {
					continue;
				} else if (is_dir($sub_path)) {
					$files    = self::getFiles($sub_path);
					$fileList = array_merge($fileList, $files);
				} else {
					$isMatched = preg_match_all('/Request.php/', $value, $matches);
					if (!$isMatched) {
						continue;
					}
					$fileList[] = $sub_path;
				}
			}
		}
		return $fileList;
	}

	/**
	 * @param $path
	 * @return array
	 */
	public static function decodeFile($path, $fileName)
	{
		include_once $path . $fileName;
		$namespace = "\\" . str_replace("/", "\\", ucfirst($path));

		// 反射request类
		$className = substr($fileName, 0, strlen($fileName) - 4);
		try {
			$obj = new \ReflectionClass($namespace . $className);
		} catch (ReflectionException $e) {
			die("抛出错误");
		}

		// 读取解析文件
		$content = file_get_contents($path . $fileName);
		$content = explode("\n", $content);
		$str     = '';
		foreach ($content as &$c) {
			$c = trim($c);
			if (empty($c)) {
				continue;
			}
			$str .= $c . self::Split_Str;
		}
		$content = $str;
		unset($str);

		// 处理常量列表
		try {
			$constants = $obj->getConstants();
			$list      = [];
			foreach ($constants ?? [] as $constKey => $const) {
				$isMatched       = preg_match_all('/const\s+' . $constKey . '.*?];/', $content, $matches);
				$ruleStr         = str_replace("\"", "'", $matches[0][0]);      // 统一转单引号
				$strArr          = explode(self::Split_Str, $ruleStr);
				$remarks         = self::decodeRemark($const, $strArr);
				$list[$constKey] = self::joint($const, $remarks);
			}
			unset($constants);
		} catch (Throwable $e) {
			echo "【{$fileName}】常量：引用外部包，无法生成，请手动填写" . PHP_EOL;
		}

		// 处理函数
		$constants = [];
		$methods   = $obj->getMethods();
		/** @var ReflectionMethod $method */
		foreach ($methods ?? [] as $method) {
			$methodName = $method->getName();
			try {
				$res = $method->invoke($obj);
			} catch (Throwable $e) {
				echo "【{$fileName}】函数：" . $methodName . "引用外部包，无法生成，请手动填写" . PHP_EOL;
				continue;
			}
			$constants[$methodName] = $res;
		}
		foreach ($constants ?? [] as $constKey => $const) {
			$isMatched       = preg_match_all('/' . $constKey . '\(\).*?];/', $content, $matches);
			$ruleStr         = str_replace("\"", "'", $matches[0][0]);      // 统一转单引号
			$strArr          = explode(self::Split_Str, $ruleStr);
			$remarks         = self::decodeRemark($const, $strArr);
			$list[$constKey] = self::joint($const, $remarks);
		}

		return $list;
	}

	/**
	 * 拼接单个请求的
	 * @param array $const   单个const配置
	 * @param array $remarks 备注列表
	 * @return array
	 */
	private static function joint($const, $remarks = [])
	{
		$properties = [];       // 属性值
		$required   = [];       // 记录必填
		foreach ($const ?? [] as $key => $condition) {
			if (!is_array($condition)) {
				$condition = explode("|", $condition);
			}
			$type = "string";
			if (in_array("integer", $condition)) {
				$type = "number";
			} else if (in_array("array", $condition)) {
				$type = "array";
			}
			$properties[$key] = [
				"type" => $type,
			];
			if (in_array("required", $condition)) {
				$required[] = $key;
			}
			if (isset($remarks[$key])) {
				$properties[$key]['description'] = $remarks[$key];
			}
		}
		return [
			"type"       => "object",
			"title"      => "empty object",
			"properties" => $properties,
			"required"   => $required,
		];
	}

	/**
	 * 解析字符串获取注释
	 * @param $const
	 * @param $strArr
	 * @return array
	 */
	private static function decodeRemark($const, $strArr)
	{
		$remarks = [];      // 记录备注['key'=>'备注信息']
		foreach ($const ?? [] as $key => $condition) {
			foreach ($strArr as $str) {
				if (strpos($str, "'" . $key . "'") !== false) {
					$isMatched = preg_match_all('/\/\/.*/', $str, $matches);
					if (!$isMatched) break;
					$remark        = substr($matches[0][0], 2, strlen($matches[0][0]) - 1);
					$remark        = trim($remark);
					$remarks[$key] = $remark;
					break;
				}
			}
		}
		return $remarks;
	}

	/**
	 * 生成最终文件
	 * @param $path
	 * @param $fileName
	 * @param $list
	 */
	public static function createFile($path, $fileName, $list)
	{
		self::mkdirs($path);
		$str = '';
		foreach ($list as $key => $item) {
			$str  .= $key . PHP_EOL;
			$json = json_encode($item, JSON_UNESCAPED_UNICODE);
			$str  .= $json . PHP_EOL . PHP_EOL;
		}
		file_put_contents($path . $fileName, $str);
	}
}
