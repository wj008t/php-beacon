<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-11-27
 * Time: 下午11:35
 */

namespace beacon\widget;


use beacon\Field;
use beacon\Request;
use beacon\Utils;

class MultipleDialog extends Hidden
{

    public function code(Field $field, $attr = [])
    {
        $attr['yee-module'] = 'multiple-dialog';
        $attr['type'] = 'text';
        if (!empty($field->value)) {
            $values = [];
            if (is_array($field->value)) {
                $values = $field->value;
            } else if (Utils::isJson($field->value)) {
                $values = json_decode($field->_value, 1);
            }
            $textFunc = $field->getFunc('text');
            if ($textFunc && is_callable($textFunc)) {
                $data = [];
                foreach ($values as $val) {
                    $text = call_user_func($textFunc, $val);
                    $data[] = ['value' => $val, 'text' => $text];
                }
                $attr['data-text'] = $data;
            } else {
                $data = [];
                foreach ($values as $val) {
                    $data[] = ['value' => $val, 'text' => $val];
                }
                $attr['data-text'] = $data;
            }
        }
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<input ' . join(' ', $attr) . ' />';
    }


    public function assign(Field $field, array $input)
    {
        $values = Request::input($input, $field->boxName . ':a', []);
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
        } else if (is_string($temp) && Utils::isJson($temp)) {
            $field->value = json_decode($temp, true);
        } else {
            $field->value = null;
        }
    }
}