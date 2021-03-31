<?php
/**
 * Class YapiCommand
 * 作者: su
 * 时间: 2021/3/16 14:46
 * 备注:
 */

namespace Chive\Command;


use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;

/**
 * @Command
 */
class YapiCommand extends HyperfCommand
{

	/** @var string 文件路径 */
	protected $createPath = 'runtime/Yapi.txt';

	public function __construct()
	{
		parent::__construct('chive:yapi');
	}

	public function configure()
	{
		parent::configure();
		$this->setDescription('根据数据表生成Yapi接口文档返回格式');
	}

	public function handle()
	{
		$this->line('开始生成文件...', 'info');
		$tableNames = $this->getAllTableName();
		$this->decodeTable($tableNames);
		$this->line('生成文件完成，路径【' . $this->createPath . '】', 'info');
	}

	/**
	 * 获取所有表名
	 * @return array
	 */
	public function getAllTableName()
	{
		$sql  = "SELECT table_name,table_comment FROM information_schema.TABLES WHERE table_schema = '" . env('DB_DATABASE') . "' AND table_type = 'base table'";
		$list = Db::select($sql);
		return $list;
	}

	/**
	 * 读取所有表信息，转换成Yapi格式
	 * @param $list
	 */
	public function decodeTable($list)
	{
		$content = '';
		/** @var \stdClass $obj */
		foreach ($list as $obj) {
			$tableName = $obj->table_name;

			$list = Db::select("SELECT `column_name`, `data_type`, `column_comment` FROM information_schema. COLUMNS WHERE `table_schema` = '" . env('DB_DATABASE') . "' AND `table_name` = '{$tableName}' ORDER BY ORDINAL_POSITION;");


			$properties = [];
			/** @var \stdClass $data */
			foreach ($list as $key => $data) {
				$dKey        = $data->column_name;
				$type        = 'string';
				$description = $data->column_comment;
				switch ($data->data_type) {
					case 'int':
					case 'tinyint':
					case 'bigint':
						$type = 'number';
						break;
				}
				// key以_at结尾，默认为时间格式
				if (substr($dKey, -3) == '_at') {
					$type        = 'string';
					$description .= '[格式：Y-m-d H:i:s]';
				}
				$properties[$dKey] = [
					'type'        => $type,
					'description' => $description,
				];

				// key以x_id结尾，默认加上x_name字段
				if (substr($dKey, -3) == '_id') {
					$_name              = substr($dKey, 0, strlen($dKey) - 3) . '_name';
					$properties[$_name] = [
						'type'        => 'string',
						'description' => $description . '[对应名称]',
					];
				}
			}

			$toFormatArr  = [
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
			$toFormatArr2 = [
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'type'       => 'object',
				'properties' => [
					'code'  => ['type' => 'number'],
					'msg'   => ['type' => 'string'],
					'total' => ['type' => 'number'],
					'data'  => [
						'type'       => 'object',
						'properties' => $properties,
						'required'   => [],
					],
				]
			];

			$content .= "【" . $tableName . "】" . "【" . $obj->table_comment . "】二维数组\n" . json_encode($toFormatArr, JSON_UNESCAPED_UNICODE) . "\n";
			$content .= "【" . $tableName . "】" . "【" . $obj->table_comment . "】一维数组\n" . json_encode($toFormatArr2, JSON_UNESCAPED_UNICODE) . "\n\n";

		}

		file_put_contents($this->createPath, $content);
	}
}