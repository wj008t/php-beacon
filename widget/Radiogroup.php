<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 14:06
 */

namespace  beacon\widget;


use beacon\Console;
use beacon\Field;
use beacon\Utils;

class Radiogroup extends Hidden
{
    public function code(Field $field, $args)
    {

        $id = isset($args['id']) ? $args['id'] : $field->boxId;
        $name = isset($args['name']) ? $args['name'] : $field->boxName;
        $class = isset($args['class']) ? $args['class'] : $field->boxClass;
        $style = isset($args['style']) ? $args['style'] : $field->boxStyle;
        $options = isset($args['@options']) ? $args['@options'] : $field->options;
        $options = $options == null ? [] : $options;
        $value = isset($args['value']) ? $args['value'] : $field->_value;
        $args['value'] = '';
        $args['style'] = '';
        $args['class'] = '';
        $args['name'] = '';
        $args['type'] = '';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        $out = [];
        if ($field->useUlList) {
            $out[] = '<ul id="radio-group-' . $id . '"';
            if ($class !== null) {
                $out[] = ' class="' . $class . '"';
            }
            if ($style !== null) {
                $out[] = ' style="' . $style . '"';
            }
            $out[] = '>' . "\n";
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
                $selected = strval($val) == strval($value) ? ' checked="checked"' : '';
                $out[] = '<li><label>';
                if ($endKey === $key) {
                    $selected .= ' ' . join(' ', $attr);
                }
                $out[] = '<input type="radio" name="' . $name . '" value="' . htmlspecialchars($val) . '"' . $selected;
                foreach ($item as $k => $dval) {
                    if (preg_match('@^data-\w+$@', $k, $m)) {
                        if (is_array($dval)) {
                            $out[] = ' ' . $k . '="' . htmlspecialchars(json_encode($dval)) . '"';
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
                $out[] = '</span></label></li>' . "\n";
            }
            $out[] = '</ul>' . "\n";
        } else {
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
                $selected = strval($val) == strval($value) ? ' checked="checked"' : '';
                $out[] = '<label';
                if ($class !== null) {
                    $out[] = ' class="' . $class . '"';
                }
                if ($style !== null) {
                    $out[] = ' style="' . $style . '"';
                }
                $out[] = '>';
                if ($endKey === $key) {
                    $selected .= ' ' . join(' ', $attr);
                }
                $out[] = '<input type="radio" name="' . $name . '" value="' . htmlspecialchars($val) . '"' . $selected;
                foreach ($item as $k => $dval) {
                    if (preg_match('@^data-\w+$@', $k, $m)) {
                        if (is_array($dval)) {
                            $out[] = ' ' . $k . '="' . htmlspecialchars(json_encode($dval)) . '"';
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
        }
        return join('', $out);
    }

}