<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 14:06
 */

namespace beacon\widget;


use beacon\Field;
use beacon\Logger;

class RadioGroup extends Hidden
{
    public function code(Field $field, $attr = [])
    {
        $name = isset($attr['name']) ? $attr['name'] : $field->boxName;
        $class = isset($attr['class']) ? $attr['class'] : $field->boxClass;
        $style = isset($attr['style']) ? $attr['style'] : $field->boxStyle;
        $options = isset($attr['@options']) ? $attr['@options'] : $field->options;
        $options = $options == null ? [] : $options;
        $value = isset($attr['value']) ? $attr['value'] : $field->value;
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
                $item = ['value' => $item];
            }
            $text = isset($item['text']) ? $item['text'] : (isset($item[1]) ? $item[1] : (isset($item[0]) ? $item[0] : null));
            $tips = isset($item['tips']) ? $item['tips'] : (isset($item[2]) ? $item[2] : null);
            $val = isset($item['value']) ? $item['value'] : (isset($item[0]) ? $item[0] : null);
            if ($val === null) {
                $val = $text;
            }
            $inpAttr = strval($val) == strval($value) ? ' checked="checked"' : '';
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

            $out[] = '<input type="radio" name="' . $name . '" value="' . htmlspecialchars($val) . '"' . $inpAttr;
            foreach ($item as $k => $dval) {
                if ($k == 'bind') {
                    if (is_array($dval)) {
                        $out[] = ' data-' . $k . '="' . htmlspecialchars(json_encode($dval)) . '"';
                    } else {
                        $out[] = ' ' . $k . '="' . htmlspecialchars($dval) . '"';
                    }
                }
            }
            $out[] = '/>';
            $out[] = '<span>' . htmlspecialchars($text);
            if (!empty(strval($tips))) {
                $out[] = '<em>' . htmlspecialchars($tips) . '</em>';
            }
            $out[] = '</span></label>' . "\n";
        }
        return join('', $out);
    }

}