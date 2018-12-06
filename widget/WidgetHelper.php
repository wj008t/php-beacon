<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-11-15
 * Time: 下午10:17
 */

namespace beacon\widget;


use beacon\Field;
use beacon\Logger;
use beacon\Request;
use beacon\Utils;

class WidgetHelper
{
    /**
     * 根据类型 获取对应的值
     * @param string $varType
     * @param array $input
     * @param string $key
     * @return null
     */
    public static function getValue(string $varType, array $input, string $key)
    {
        switch ($varType) {
            case 'bool':
            case 'boolean':
                return Request::input($input, $key . ':b', false);
            case 'int':
            case 'integer':
                $val = Request::input($input, $key . ':s', '');
                if (preg_match('@[+-]?\d*\.\d+@', $val)) {
                    return Request::input($input, $key . ':f', 0);
                } else {
                    return Request::input($input, $key . ':i', 0);
                }
            case 'double':
            case 'float':
                return Request::input($input, $key . ':f', 0);
            case 'string':
                return Request::input($input, $key . ':s', '');
            case 'array':
                return Request::input($input, $key . ':a', []);
            default :
                return Request::input($input, $key, '');
        }
        return null;
    }

    /**
     * 类型转换
     * @param $value
     * @param $type
     * @return array|bool|int|string
     */
    public static function convertType($value, $type)
    {
        switch ($type) {
            case 'bool':
            case 'boolean':
                if (is_string($value)) {
                    return $value === '1' || $value === 'on' || $value === 'yes' || $value === 'true';
                }
                return !!$value;
            case 'int':
            case 'integer':
                if (is_int($value) || is_float($value) || is_double($value) || is_bool($value)) {
                    return intval($value);
                } else if (is_string($value) && is_numeric($value)) {
                    return intval($value);
                }
                return 0;
            case 'double':
            case 'float':
                if (is_int($value) || is_float($value) || is_double($value) || is_bool($value)) {
                    return $value;
                }
                if (is_string($value) && is_numeric($value)) {
                    return floatval($value);
                }
                return 0;
            case 'array':
                if (is_array($value)) {
                    return $value;
                }
                return [$value];
            default :
                return strval($value);
        }
    }

    /**
     * 合并属性
     * @param Field $field
     * @param array $attrs
     * @return array
     */
    public static function mergeAttributes(Field $field, $attrs = null)
    {
        $attributes = $field->getAttributes();
        if (is_array($attrs)) {
            foreach ($attrs as $key => $val) {
                $key = Utils::camelToAttr($key);
                if ($key[0] == '@') {
                    continue;
                }
                $attributes[$key] = $val;
            }
        }
        $base = [];
        // Logger::log($attributes);
        /*
        if (isset($attributes['data-dynamic'])) {
            if (!isset($attributes['yee-module'])) {
                $attributes['yee-module'] = 'dynamic';
            } else {
                $attributes['yee-module'] .= ' dynamic';
            }
        }*/
        foreach ($attributes as $name => $val) {
            if ($val === null || $val === '') {
                continue;
            }
            if ($name == 'data-val') {
                $name = 'data-v@rule';
            } //验证数据
            else if (preg_match('@^data-val-(.*)$@', $name, $m)) {
                $name = 'data-v@' . $m[1];
            }
            if (is_array($val)) {
                array_push($base, $name . '="' . htmlspecialchars(json_encode($val, JSON_UNESCAPED_UNICODE)) . '"');
            } else if (is_bool($val)) {
                array_push($base, $name . '="' . ($val ? 1 : 0) . '"');
            } else if (is_string($val)) {
                array_push($base, $name . '="' . htmlspecialchars($val) . '"');
            } else {
                array_push($base, $name . '="' . $val . '"');
            }
        }
        return $base;
    }

}