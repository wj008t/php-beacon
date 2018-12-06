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

class Linkage implements WidgetInterface
{

    public function code(Field $field, $attr = [])
    {
        $values = isset($attr['value']) ? $attr['value'] : $field->value;
        if (is_string($values) && Utils::isJson($values)) {
            $values = json_decode($values, true);
        }
        if (!is_array($values)) {
            $values = [];
        }
        $values = array_map(function ($v) {
            return strval($v);
        }, $values);
        $attr['value'] = '';
        if (count($values) > 0) {
            $attr['value'] = $values;
        }
        if ($field->names !== null && is_array($field->names)) {
            foreach ($field->names as $idx => $item) {
                $pname = is_string($item) ? $item : (isset($item['field']) ? $item['field'] : '');
                $level = $idx + 1;
                $attr['data-name' . $level] = $pname;
            }
        }
        $args['yee-module'] = 'linkage';
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<input ' . join(' ', $attr) . ' />';
    }


    public function assign(Field $field, array $input)
    {
        if ($field->names !== null && is_array($field->names)) {
            $values = [];
            foreach ($field->names as $idx => $item) {
                $name = is_string($item) ? $item : (empty($item['field']) ? null : $item['field']);
                $type = is_string($item) ? 'string' : (empty($item['type']) ? 'string' : $item['type']);
                if (empty($name)) {
                    continue;
                }
                $values[] = WidgetHelper::getValue($type, $input, $name);
            }
            return $field->value = $values;
        }
        $boxName = $field->boxName;
        $values = Request::input($input, $boxName, null);
        if (is_array($values)) {
            return $field->value = $values;
        }
        if (Utils::isJson($values)) {
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
                $type = is_string($item) ? 'string' : (empty($item['type']) ? 'string' : $item['type']);
                if (empty($name)) {
                    continue;
                }
                $values[$name] = WidgetHelper::convertType(isset($field->value[$idx]) ? $field->value[$idx] : '', $type);
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
                $type = is_string($item) ? 'string' : (empty($item['type']) ? 'string' : $item['type']);
                if (empty($name)) {
                    continue;
                }
                $temps[] = WidgetHelper::convertType(isset($values[$name]) ? $values[$name] : '', $type);
            }
            return $field->value = $temps;
        }
        $temps = isset($values[$field->name]) ? '' : $values[$field->name];
        if (is_array($temps)) {
            return $field->value = $temps;
        }
        if (Utils::isJson($temps)) {
            $temps = json_decode($temps);
            if (is_array($temps)) {
                return $field->value = $temps;
            }
        }
        return $field->value = null;
    }
}