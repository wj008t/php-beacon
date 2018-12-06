<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 21:34
 */

namespace beacon\widget;

use beacon\Field;
use beacon\Request;
use beacon\Utils;

class CheckGroup implements WidgetInterface
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
        $name = isset($attr['name']) ? $attr['name'] : $field->boxName;
        $class = isset($attr['class']) ? $attr['class'] : $field->boxClass;
        $style = isset($attr['style']) ? $attr['style'] : $field->boxStyle;
        $options = isset($attr['@options']) ? $attr['@options'] : $field->options;
        $options = $options == null ? [] : $options;
        $attr['value'] = '';
        $attr['style'] = '';
        $attr['class'] = '';
        $attr['name'] = '';
        $attr['type'] = '';
        $inpClass = isset($attr['inp-class']) ? $attr['inp-class'] : $field->boxInpClass;
        $attr['inp-class'] = '';
        $attributes = $field->getAttributes();
        $attr = WidgetHelper::mergeAttributes($field, $attr);

        $out = [];
        $keys = array_keys($options);
        $endKey = end($keys);
        foreach ($options as $key => $item) {
            if ($item == null) {
                continue;
            }
            if (!is_array($item)) {
                $item = ['value' => $item, 'text' => $item];
            }
            $val = isset($item['value']) ? $item['value'] : (isset($item[0]) ? $item[0] : null);
            $text = isset($item['text']) ? $item['text'] : (isset($item[1]) ? $item[1] : (isset($item[0]) ? $item[0] : null));
            $tips = isset($item['tips']) ? $item['tips'] : (isset($item[2]) ? $item[2] : null);
            if ($val === null) {
                $val = $text;
            }
            $inpAttr = in_array(strval($val), $values) === true ? ' checked="checked"' : '';
            $out[] = '<label';
            if ($class !== null) {
                $out[] = ' class="' . $class . '"';
            }
            if ($style !== null) {
                $out[] = ' style="' . $style . '"';
            }
            $out[] = '>';
            if ($endKey === $key) {
                $inpAttr .= ' ' . join(' ', $attr);
            }
            if ($inpClass) {
                $inpAttr .= ' class="' . $inpClass . '"';
            }
            if (!empty($attributes['disabled'])) {
                $inpAttr .= ' disabled="' . $attributes['disabled'] . '"';
            }
            if (!empty($attributes['readonly'])) {
                $inpAttr .= ' readonly="' . $attributes['readonly'] . '"';
            }
            $out[] = '<input type="checkbox" name="' . $name . '[]" value="' . htmlspecialchars($val) . '"' . $inpAttr . '/>';
            $out[] = '<span>' . htmlspecialchars($text);
            if (!empty(strval($tips))) {
                $out[] = '<em>' . htmlspecialchars($tips) . '</em>';
            }
            $out[] = '</span></label>' . "\n";
        }
        return join('', $out);
    }

    public function assign(Field $field, array $input)
    {
        $values = Request::input($input, $field->boxName . ':a', []);
        $temp = [];
        if (is_array($values)) {
            foreach ($values as $item) {
                $temp[] = WidgetHelper::convertType($item, $field->itemType);
            }
        }
        $field->value = $temp;
        return $field->value;
    }

    public function fill(Field $field, array &$values)
    {
        $field->value = $field->value == null ? [] : $field->value;
        //处理按位填入数据库
        if ($field->bitComp) {
            $value = 0;
            if (is_array($field->value)) {
                foreach ($field->value as $item) {
                    if ((is_string($item) || is_integer($item)) && preg_match('@^\d+$@', $item)) {
                        $opt_value = intval($item);
                        $value = $value | $opt_value;
                    }
                }
            }
            $values[$field->name] = $value;
            return;
        }
        //处理按字段拆分填入数据值
        if (isset($field->names) && is_array($field->names)) {
            foreach ($field->names as $item) {
                $name = is_string($item) ? $item : (empty($item['field']) ? null : $item['field']);
                $values[$name] = 0;
            }
            $options = $field->options == null ? [] : $field->options;
            $opts = [];
            foreach ($options as $item) {
                if (!is_array($item)) {
                    $opts[] = WidgetHelper::convertType($item, $field->itemType);
                } else if (isset($item['value'])) {
                    $opts[] = WidgetHelper::convertType($item['value'], $field->itemType);
                } else if (isset($item['text'])) {
                    $opts[] = WidgetHelper::convertType($item['text'], $field->itemType);
                }
            }
            foreach ($field->names as $idx => $item) {
                $name = is_string($item) ? $item : (empty($item['field']) ? null : $item['field']);
                $val = isset($opts[$idx]) ? $opts[$idx] : null;
                if ($val !== null && in_array($val, $field->value)) {
                    $values[$name] = 1;
                }
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
        //按位解析出选项值
        if ($field->bitComp) {
            $value = isset($values[$field->name]) ? intval($values[$field->name]) : 0;
            $temps = [];
            $options = $field->options == null ? [] : $field->options;
            foreach ($options as $item) {
                $opt_value = null;
                if (!is_array($item)) {
                    $opt_value = WidgetHelper::convertType($item, $field->itemType);
                } else if (isset($item['value'])) {
                    $opt_value = WidgetHelper::convertType($item['value'], $field->itemType);
                } else if (isset($item['text'])) {
                    $opt_value = WidgetHelper::convertType($item['text'], $field->itemType);
                }
                if (empty($opt_value) || !preg_match('@^\d+$@', $opt_value)) {
                    throw new Exception('使用位运算的选项值必须是数字形式。');
                }
                $temps[] = $value & intval($opt_value) > 0 ? 1 : 0;
            }
            return $field->value = $temps;
        }
        //按字段内容解析出选项值
        if (isset($field->names) && is_array($field->names)) {
            $options = $field->options == null ? [] : $field->options;
            $temp_values = [];
            $opts = [];
            foreach ($options as $item) {
                if (!is_array($item)) {
                    $opts[] = WidgetHelper::convertType($item, $field->itemType);
                } else if (isset($item['value'])) {
                    $opts[] = WidgetHelper::convertType($item['value'], $field->itemType);
                } else if (isset($item['text'])) {
                    $opts[] = WidgetHelper::convertType($item['text'], $field->itemType);
                }
            }
            foreach ($field->names as $idx => $item) {
                $name = is_string($item) ? $item : (empty($item['field']) ? null : $item['field']);
                $opt_value = isset($opts[$idx]) ? $opts[$idx] : null;
                if (isset($values[$name]) && intval($values[$name]) == 1) {
                    $temp_values[] = $opt_value;
                }
            }
            return $field->value = $temp_values;
        }
        $temps = isset($values[$field->name]) ? $values[$field->name] : '';
        if (is_array($temps)) {
            return $field->value = $temps;
        }
        if (Utils::isJson($temps)) {
            $temps = json_decode($temps, true);
            if (is_array($temps)) {
                return $field->value = $temps;
            }
        }
        return $field->value = null;
    }
}