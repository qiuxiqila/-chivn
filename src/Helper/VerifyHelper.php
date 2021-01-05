<?php

namespace Chive\Helper;

use Chive\Exception\BusinessException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


/**
 * 验证类
 */
class VerifyHelper
{

    /**
     * @Inject()
     * @var ValidatorFactoryInterface
     */
    protected $validationFactory;

    /**
     * @param array $params  校验的数据
     * @param array $rule    校验规则，只返回校验的字段，做数据过滤
     * @param array $message 自定义消息提醒
     * @return array
     */
    public function check(array $params, array $rule = [], $message = [])
    {

        $validator = $this->validationFactory->make($params, $rule, $message);
        if ($validator->fails()) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, implode(",", $validator->errors()->all()));
        }

        $data = $validator->validated();
        foreach ($data as $key => $value) {
            self::verifyConvertType($rule[$key], $data[$key]);
        }

        return $data;
    }

    /**
     * 字段值类型转换
     * @param string|array $rule_key
     * @param        $value
     */
    public static function verifyConvertType($rule_key, &$value): void
    {
        if(is_string($rule_key))$rule_key = explode('|',$rule_key);

        if(in_array('integer',$rule_key))$value = intval($value);
        if(in_array('string',$rule_key))$value = strval($value);

    }
}