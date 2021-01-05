<?php
/**
 * Class AbstractDao
 * 作者: su
 * 时间: 2020/11/21 14:23
 * 备注:
 */

namespace Chive\Dao;

use Chive\Exception\BusinessException;
use Chive\Helper\CommonHelper;
use Chive\Helper\ErrorHelper;
use Chive\Model\Casts\TimeCasts;
use Chive\Model\Common;
use Chive\Model\Model;
use Hyperf\Database\Model\Builder;

abstract class AbstractDao
{
    /** @var int 不含删除的列 */
    const NOT_DELETE_QUERY = 0;
    /** @var int 所有的列 */
    const COMMON_QUERY = 1;
    /** @var int 软删除的列 */
    const DELETE_QUERY = 2;

    /**
     * model类名[必须继承赋值]
     * @var Model
     */
    protected $modelClass = null;

    /**
     * @var string 主键名
     */
    protected $primaryKey = 'id';

    /**
     * @var array 需要转换格式的字段
     */
    protected $withCasts = null;

    /**
     * 排序键名， 默认为主键名
     * @var string
     */
    protected $orderByKey = null;

    /**
     * 排序方式，desc倒序，asc正序
     * @var string
     */
    protected $orderType = 'desc';

    /**
     * 列表
     * @param array    $params
     * @param string[] $fields
     * @param bool     $isTransition 是否需要循环转换格式,false不转换。true转换，需重写tranFormat方法
     * @return array
     */
    public function getList(array $params, array $fields = [], $isTransition = false)
    {
        $page_size = !empty($params['page_size']) ? intval($params['page_size']) : CommonHelper::getDefaultPageSize();
        if ($page_size == -1) {
            return [ErrorHelper::RET_DATA => $this->getAllList($params, $fields, $isTransition), ErrorHelper::RET_TOTAL => 1];
        }

        $query = $this->getQuery();

        // 搜索条件
        $res = $this->condition($query, $params);
        if (!$res) {
            return [];
        }

        if (!empty($fields)) {
            $query->select($fields);
        }
        if (!empty($this->withCasts)) {
            $query->withCasts($this->withCasts);
        }
        if ($this->orderType == 'desc') {
            empty($this->orderByKey)
                ? $query->latest($this->primaryKey)
                : $query->latest($this->orderByKey);
        } else {
            empty($this->orderByKey)
                ? $query->oldest($this->primaryKey)
                : $query->oldest($this->orderByKey);
        }
        $list = $query
            ->paginate($page_size)
            ->toArray();
        if (empty($list['data'])) {
            return [];
        }
        if ($isTransition) {
            $list['data'] = $this->tranFormatArr($list['data']);
        }
        return Common::returnList($list);
    }

    /**
     * 获取所有列表
     * @param       $params
     * @param array $fields
     * @param bool  $isTransition
     * @return array|Builder[]|\Hyperf\Database\Model\Collection
     */
    public function getAllList($params, $fields = [], $isTransition = false)
    {
        $query = $this->getQuery();

        // 搜索条件
        $res = $this->condition($query, $params);
        if (!$res) {
            return [];
        }

        if (!empty($fields)) {
            $query->select($fields);
        }
        if (!empty($this->withCasts)) {
            $query->withCasts($this->withCasts);
        }
        if ($this->orderType == 'desc') {
            empty($this->orderByKey)
                ? $query->latest($this->primaryKey)
                : $query->latest($this->orderByKey);
        } else {
            empty($this->orderByKey)
                ? $query->oldest($this->primaryKey)
                : $query->oldest($this->orderByKey);
        }
        $list = $query->get()->toArray();
        if (empty($list)) {
            return [];
        }
        if ($isTransition) {
            $list = $this->tranFormatArr($list);
        }
        return $list;
    }

    /**
     * 搜索条件
     * @param Builder $query
     * @param array   $params
     * @return bool
     */
    public function condition($query, $params)
    {
        $property = self::getModelProperty($this->modelClass);
        if (empty($property)) {
            return false;
        }
        foreach ($property as $key) {
            if (!empty($params[$key])) {
                $query->where($key, '=', $params[$key]);
            }
        }
        // 查询软删除等
        if (isset($params['is_deleted'])) {
            if ($params['is_deleted'] == true) {
                $query->onlyTrashed();
            } else {
                $query->withTrashed();
            }
        }
        return true;
    }

    /**
     * 将读库信息转换格式[单条]
     * @param array $data
     * @return array
     */
    public function tranFormatOne($data): array
    {
        return $data;
    }

    /**
     * 将读库信息转换格式[多条]
     * @param $data
     * @return array
     */
    public function tranFormatArr($data): array
    {
        return $data;
    }

    /**
     * 获取一条记录
     * @param mixed  $val    查询值
     * @param string $key    字段名,为空时或为主键，查询缓存
     * @param array  $fields 字段
     * @return \Hyperf\Database\Model\Builder|\Hyperf\Database\Model\Model|object|null
     */
    public function getOneByKey($val, $key = '', $fields = [])
    {
        if ($key == '') {
            $key = $this->primaryKey;
        }
        $query = $this->getQuery();
        $query->where($key, '=', $val);
        if (!empty($fields)) {
            $query->select($fields);
        }
        if (!empty($this->withCasts)) {
            $query->withCasts($this->withCasts);
        }
        return $query->first();
    }

    /**
     * 获取一条记录，返回数组
     * @param        $val
     * @param string $key
     * @param array  $fields
     * @return array|null
     */
    public function getOneArrByKey($val, $key = '', $fields = [])
    {
        $model = $this->getOneByKey($val, $key, $fields);
        if (!empty($model)) {
            $data = $model->toArray();
            $data = $this->tranFormatOne($data);
            return $data;
        }
        return null;
    }

    /**
     * 根据条件获取一条记录，可接收多条件
     * @param array $where
     * @param array $fields
     * @return Builder|\Hyperf\Database\Model\Model|object|null
     */
    public function getOne($where, $fields = [])
    {
        $query = $this->getQuery();
        $res   = $this->condition($query, $where);
        if (!$res) {
            return null;
        }
        if (!empty($fields)) {
            $query->select($fields);
        }
        if (!empty($this->withCasts)) {
            $query->withCasts($this->withCasts);
        }
        return $query->first();
    }

    /**
     * 根据条件获取一条记录指定字段值，可接收多条件
     * @param        $where
     * @param string $value
     * @return \Hyperf\Utils\HigherOrderTapProxy|mixed|string
     */
    public function getOneVal($where, $value = '')
    {
        if (!in_array($value, self::getModelProperty($this->modelClass))) return '';
        $query = $this->getQuery();
        $res   = $this->condition($query, $where);
        if (!$res) {
            return '';
        }
        return $query->value($value);
    }


    /**
     * 根据条件获取一条记录 转数组
     * @param array $where
     * @param array $fields
     * @return array|null
     */
    public function getOneArr($where, $fields = [])
    {
        $model = $this->getOne($where, $fields);
        if (!empty($model)) {
            $data = $model->toArray();
            $data = $this->tranFormatOne($data);
            return $data;
        }
        return [];
    }

    /**
     * 插入记录
     * @param array $params
     * @return Model|false
     */
    public function add($params)
    {
        $class = $this->modelClass;
        /** @var Model $model */
        $model    = new $class();
        $property = self::getModelProperty($class);
        if (empty($property)) {
            return false;
        }
        foreach ($property as $key) {
            if (isset($params[$key])) {
                $model->$key = $params[$key];
            }
        }
        try {
            $model->save();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '数据入库失败', $ex);
        }
        return $model;
    }

    /**
     * 更新记录
     * @param array $where
     * @param array $data
     * @return int
     */
    public function update($where, $data)
    {
        $query = $this->getQuery();
        $res   = $this->condition($query, $where);
        if (!$res) {
            return false;
        }
        $data = $this->filterUpdateData($data);
        if (empty($data)) {
            return false;
        }
        return $query->update($data);
    }

    /**
     * 根据某个键更新
     * @param        $val
     * @param        $data
     * @param string $key
     * @return int
     */
    public function updateByKey($val, $data, $key = '')
    {
        if ($key == '') {
            $key = $this->primaryKey;
        }
        return $this->update([$key => $val], $data);
    }

    /**
     * 根据主键更新字段
     * @param $params
     * @return bool
     */
    public function updateOne($params)
    {
        if (!isset($params[$this->primaryKey])) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '查询不到记录');
        }
        return $this->update([$this->primaryKey => $params[$this->primaryKey]], $params);
    }

    /**
     * 过滤更新字段信息
     * @param array $data      更新的字段
     * @param bool  $offPriKey 是否需要排除主键，true去掉，false不去掉
     * @return array
     */
    public function filterUpdateData($data, $offPriKey = true)
    {
        $class      = $this->modelClass;
        $property   = self::getModelProperty($class);
        $updateData = [];
        foreach ($property as $key) {
            if ($offPriKey && $key == $this->primaryKey) {
                continue;
            }
            if (isset($data[$key])) {
                $updateData[$key] = $data[$key];
            }
        }
        if (empty($updateData)) {
            return [];
        }
        return $updateData;
    }

    /**
     * 删除（软删除）
     * @param $where
     * @return int|mixed
     */
    public function del($where)
    {
        if (empty($where)) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '禁止运行不带where的删除');
        }
        $query = $this->getQuery();
        $res   = $this->condition($query, $where);
        if (!$res) {
            return false;
        }
        return $query->delete();
    }

    /**
     * 恢复（软删除数据）
     * @param $where
     * @return int|mixed
     */
    public function restore($where)
    {
        if (empty($where)) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '禁止运行不带where的软删除恢复');
        }
        $query = $this->getQuery();
        //必要的软删除条件
        $query->onlyTrashed();
        $res = $this->condition($query, $where);
        if (!$res) {
            return false;
        }
        return $query->restore();
    }


    /**
     * 强制删除软删除记录
     * @param $where
     * @return int
     */
    public function forceDelete($where)
    {
        if (empty($where)) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '禁止运行不带where的删除');
        }
        $query = $this->getQuery();
        $res   = $this->condition($query, $where);
        if (!$res) {
            return false;
        }
        return $query->forceDelete();
    }

    /**
     * 根据主键删除
     * @param        $val
     * @param string $key 键名
     * @return int|mixed
     */
    public function delByPriKey($val, $key = '')
    {
        if ($key == '') {
            $key = $this->primaryKey;
        }
        return $this->del([$key => $val]);
    }

    /**
     * 获取主键
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * 简单统计数量
     * @param array  $where    条件
     * @param string $countKey 需要统计的字段，默认为*
     * @return int
     */
    public function count($where, $countKey = '')
    {
        if (empty($countKey)) {
            $countKey = $this->primaryKey;
        }
        $query = $this->getQuery();
        $res   = $this->condition($query, $where);
        if (!$res) {
            return false;
        }
        return $query->count($countKey);
    }

    /**
     * groupBy统计
     * @param       $groupBy
     * @param       $params
     * @param       $fields
     * @return array
     */
    public function groupBy($params, $fields, $groupBy)
    {
        if (empty($fields)) {
            $fields = ["count({$this->primaryKey}) as countNum"];
        }
        $query = $this->getQuery();
        $query->select($fields);
        $res = $this->condition($query, $params);
        if (!$res) {
            return [];
        }
        $query->groupBy($groupBy);
        $list = $query->get();
        if (empty($list)) {
            return [];
        }
        return $list->toArray();
    }

    /**
     * 插入多条数据，自动处理withCasts类型的数据
     * @param array $list      二维数组
     * @param bool  $withCasts 是否组装转换参数
     * @return bool
     */
    public function insertAll(array $list, $withCasts = false)
    {
        $class = $this->modelClass;
        // 组装参数
        if (isset($this->withCasts) && $withCasts == true) {
            foreach ($list as $key => &$value) {
                foreach ($this->withCasts as $k => $type) {
                    if (isset($value[$k])) continue;

                    // todo 更多类型待写 2020-12-7
                    switch ($type) {
                        case TimeCasts::class:
                            $value[$k] = time();
                            break;
                    }
                }
            }
        }

        try {
            return $class::insert($list);
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '插入数据失败', $ex);
        }
    }

    /**
     * 判断是否存在记录
     * @param $params
     * @return bool
     */
    public function exist($params)
    {
        $query = $this->getQuery();
        $res   = $this->condition($query, $params);
        if (!$res) {
            return false;
        }
        return $query->exists();
    }

    /**
     * 获取给定字段的列的数组
     * @param             $params
     * @param string      $column
     * @param null|string $key
     * @return array ['key'=>'column',]
     */
    public function pluck($params, $column, $key = null)
    {
        $query = $this->getQuery();
        $res   = $this->condition($query, $params);
        if (!$res) {
            return [];
        }
        return $query->pluck($column, $key)->toArray();
    }

    /**
     * 自增
     * @param     $params
     * @param     $column
     * @param int $amount
     * @return int
     */
    public function increment($params, $column, $amount = 1)
    {
        $query = $this->getQuery();
        $res   = $this->condition($query, $params);
        if (!$res) {
            return false;
        }
        return $query->increment($column, $amount);
    }

    /**
     * 自减
     * @param     $params
     * @param     $column
     * @param int $amount
     * @return false|int
     */
    public function decrement($params, $column, $amount = 1)
    {
        $query = $this->getQuery();
        $res   = $this->condition($query, $params);
        if (!$res) {
            return false;
        }
        return $query->decrement($column, $amount);
    }

    /**
     * 获取query
     * @return Builder
     */
    public function getQuery()
    {
        return $this->createQuery();
    }

    /**
     * 创建query
     * @param int $type
     * @return Builder
     */
    public function createQuery($type = self::NOT_DELETE_QUERY)
    {
        $class = $this->modelClass;
        switch ($type) {
            case self::COMMON_QUERY:
                return $class::withTrashed();
                break;
            case self::DELETE_QUERY:
                return $class::onlyTrashed();
                break;
            default:
                return $class::query();
                break;
        }
    }

    // ---------------------------------------------------------------------

    /**
     * 存model字段
     * @var array
     */
    protected static $modelFields = [];

    /**
     * 获取model属性
     * @param string $class model类名
     * @return array
     */
    public static function getModelProperty($class)
    {
        if (isset(self::$modelFields[$class])) {
            return self::$modelFields[$class];
        }
        $property = [];
        try {
            $obj = new \ReflectionClass($class);
            $doc = $obj->getDocComment();
            $arr = explode("*", $doc);
            foreach ($arr as $str) {
                if (strpos($str, "@property") !== false) {
                    $list       = explode("$", $str);
                    $list       = explode(" ", $list[1]);
                    $property[] = trim($list[0]);
                }
            }
        } catch (\ReflectionException $e) {
        }
        self::$modelFields[$class] = $property;
        return $property;
    }
}