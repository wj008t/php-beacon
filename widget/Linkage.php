<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 17:22
 */

namespace beacon\widget;


use beacon\Field;
use beacon\Request;
use beacon\Utils;

class Linkage implements BoxInterface
{

    public function code(Field $field, $args)
    {
        $values = isset($args['value']) ? $args['value'] : $field->_value;
        if (is_string($values) && Utils::isJsonString($values)) {
            $values = json_decode($values, true);
        }
        if (!is_array($values)) {
            $values = [];
        }
        foreach ($values as $key => $item) {
            $values[$key] = strval($item);
        }

        $args['value'] = '';
        if (count($values) > 0) {
            $args['value'] = $values;
        }
        if ($field->names !== null && is_array($field->names)) {
            foreach ($field->names as $idx => $item) {
                $pname = is_string($item) ? $item : (isset($item['field']) ? $item['field'] : '');
                $level = $idx + 1;
                $args['data-name' . $level] = $pname;
            }
        }
        $args['yee-module'] = 'linkage';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    private function convertType($value, $type)
    {
        if ($value === null) {
            return null;
        }
        switch ($type) {
            case 'bool':
                $value = strval($value) === '1' || strval($value) === 'on' || strval($value) === 'yes' || strval($value) === 'true';
                break;
            case 'int':
                $value = ($value === null || $value === '') ? 0 : intval($value);
                break;
            case 'float':
                $value = ($value === null || $value === '') ? 0 : floatval($value);
                break;
            default :
                break;
        }
        return $value;
    }

    public function assign(Field $field, array $data)
    {
        $request = Request::instance();
        if ($field->names !== null && is_array($field->names)) {
            $values = [];
            foreach ($field->names as $idx => $item) {
                $name = is_string($item) ? $item : (empty($item['field']) ? null : $item['field']);
                $type = is_string($item) ? 'string' : (empty($item['type']) ? 'string' : $item['field']);
                if (empty($name)) {
                    continue;
                }
                $values[] = $this->convertType($request->input($data, $name . ':s', ''), $type);
            }
            return $field->value = $values;
        }
        $boxName = $field->boxName;
        $values = $request->input($data, $boxName, null);
        if (is_array($values)) {
            return $field->value = $values;
        }
        if (Utils::isJsonString($values)) {
            $values = json_decode($values);
            if (is_array($values)) {
                return $field->value = $values;
            }
        }
        return $field->value = null;
    }

    public function fill(Field $field, array &$values)
    {
        if ($field->names !== null && is_array($field->names)) {
            if ($field->value === null || count($field->value) > count($field->names)) {
                return;
            }
            foreach ($field->names as $idx => $item) {
                $name = is_string($item) ? $item : (empty($item['field']) ? null : $item['field']);
                $type = is_string($item) ? 'string' : (empty($item['type']) ? 'string' : $item['field']);
                if (empty($name)) {
                    continue;
                }
                $values[$name] = $this->convertType(isset($field->value[$idx]) ? $field->value[$idx] : '', $type);
            }
            return;
        }
        if ($field->value === null) {
            $values[$field->name] = '';
            return;
        }
        $values[$field->name] = json_encode($field->value, JSON_UNESCAPED_UNICODE);
    }

    public function init(Field $field, array $values)
    {
        if ($field->names !== null && is_array($field->names)) {
            $temps = [];
            foreach ($field->names as $idx => $item) {
                $name = is_string($item) ? $item : (empty($item['field']) ? null : $item['field']);
                $type = is_string($item) ? 'string' : (empty($item['type']) ? 'string' : $item['field']);
                if (empty($name)) {
                    continue;
                }
                $temps[] = $this->convertType(isset($values[$name]) ? $values[$name] : '', $type);
            }
            return $field->value = $temps;
        }
        $temps = isset($values[$field->name]) ? '' : $values[$field->name];
        if (is_array($temps)) {
            return $field->value = $temps;
        }
        if (Utils::isJsonString($temps)) {
            $temps = json_decode($temps);
            if (is_array($temps)) {
                return $field->value = $temps;
            }
        }
        return $field->value = null;
    }
}