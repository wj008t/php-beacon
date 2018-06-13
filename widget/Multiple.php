<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/3/25
 * Time: 2:31
 */

namespace beacon\widget;

use beacon\Field;
use beacon\Request;
use beacon\Utils;

class Multiple extends Hidden
{
    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'multiple';
        $args['type'] = 'text';
        if (!empty($field->_value)) {
            $values = [];
            if (Utils::isJsonString($field->_value)) {
                $values = json_decode($field->_value, 1);
            }
            if ($field->textFunc !== null && is_callable($field->textFunc)) {
                $data = [];
                foreach ($values as $val) {
                    $text = call_user_func($field->textFunc, $val);
                    if (!empty($text)) {
                        $data[] = ['value' => $val, 'text' => $text];
                    }
                }
                $args['data-text'] = $data;
            } else {
                $data = [];
                foreach ($values as $val) {
                    $data[] = ['value' => $val, 'text' => $val];
                }
                $args['data-text'] = $data;
            }
        }
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $data)
    {
        $request = Request::instance();
        $values = $request->input($data, $field->boxName . ':a', []);
        $temp = [];
        if (is_array($values)) {
            foreach ($values as $item) {
                if ($field->itemType == 'integer' || $field->itemType == 'int') {
                    $temp[] = intval($item);
                } elseif ($field->itemType == 'float') {
                    $temp[] = floatval($item);
                } else {
                    $temp[] = strval($item);
                }
            }
        }
        $field->value = $temp;
        return $field->value;
    }

    public function fill(Field $field, array &$values)
    {
        $values[$field->name] = json_encode($field->value, JSON_UNESCAPED_UNICODE);
    }

    public function init(Field $field, array $values)
    {
        $temp = isset($values[$field->name]) ? $values[$field->name] : null;
        if (is_array($temp)) {
            $field->value = $temp;
        } else if (is_string($temp) && Utils::isJsonString($temp)) {
            $field->value = json_decode($temp, true);
        } else {
            $field->value = null;
        }
    }

}