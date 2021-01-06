<?php
/**
 * Class AbstractService
 * 作者: su
 * 时间: 2020/11/21 14:07
 * 备注:
 */

namespace Chive\Service;


use Chive\Dao\AbstractDao;
use Chive\Exception\BusinessException;
use Chive\Helper\ErrorHelper;

abstract class AbstractService
{
    /**
     * dao类
     * @var AbstractDao
     */
    protected $dao;

    /**
     * 获取列表
     * @param array $params
     * @return array
     */
    public function getList($params)
    {
        return $this->dao->getList($params, [], true);
    }

    /**
     * 插入数据
     * @param $params
     * @return int
     */
    public function add($params)
    {
        return $this->dao->add($params);
    }

    /**
     * 获取单条记录信息
     * @param $params
     * @return array|null
     */
    public function getOne($params)
    {
        $data = $this->dao->getOneArr($params);
        if (empty($data)) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '查询无记录');
        }
        return $data;
    }

    /**
     * 根据主键更新
     * @param $params
     * @return bool
     */
    public function update($params)
    {
        return $this->dao->updateOne($params);
    }

    /**
     * 删除
     * @param $params
     * @return int|mixed
     */
    public function delete($params)
    {
        $res = $this->dao->delByPriKey($params[$this->dao->getPrimaryKey()]);
        if (!$res) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '删除失败');
        }
        return $res;
    }
}