<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace beacon\widget;


use beacon\Field;

class Select extends Hidden
{

    public function code(Field $field, $attr = [])
    {
        $value = isset($attr['value']) ? $attr['value'] : $field->value;
        $options = isset($attr['@options']) ? $attr['@options'] : $field->options;
        $options = $options == null ? [] : $options;
        $args['value'] = '';
        $args['type'] = '';
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        $out = [];
        $out[] = '<select ' . join(' ', $attr) . '>' . "\n";
        //选项头
        if ($field->header !== null) {
            if (is_string($field->header)) {
                $out[] = '<option value="">';
                $out[] = htmlspecialchars($field->header);
                $out[] = '</option>';
            } else if (is_array($field->header) && isset($field->header['text'])) {
                if (isset($field->header['value'])) {
                    $out[] = '<option value="' . htmlspecialchars($field->header['value']) . '">';
                } else {
                    $out[] = '<option value="">';
                }
                $out[] = htmlspecialchars($field->header['text']);
                $out[] = '</option>';
            } else if (is_array($field->header) && isset($field->header[1])) {
                $out[] = '<option value="' . htmlspecialchars($field->header[0]) . '">';
                $out[] = htmlspecialchars($field->header[1]);
                $out[] = '</option>';
            }
        }
        //选项值
        foreach ($options as $item) {
            if ($item == null) {
                continue;
            }
            if (!is_array($item)) {
                $item = ['value' => $item];
            }
            $text = isset($item['text']) ? $item['text'] : (isset($item[1]) ? $item[1] : (isset($item[0]) ? $item[0] : null));
            $tips = isset($item['tips']) ? $item['tips'] : (isset($item[2]) ? $item[2] : null);
            $val = isset($item['value']) ? $item['value'] : (isset($item[0]) ? $item[0] : null);
            $group = isset($item['group']) ? $item['group'] : null;
            if ($val === null) {
                $val = $text;
            }
            if ($group !== null && is_array($group)) {
                $out[] = '<optgroup';
                if ($text !== null) {
                    $out[] = ' label="' . htmlspecialchars($text) . '"';
                }
                $out[] = '>' . "\n";
                foreach ($group as $gitem) {
                    if (!is_array($item)) {
                        $gitem = ['value' => $gitem];
                    }
                    $gtext = isset($gitem['text']) ? $gitem['text'] : (isset($gitem[1]) ? $gitem[1] : (isset($gitem[0]) ? $gitem[0] : null));
                    $gtips = isset($gitem['tips']) ? $gitem['tips'] : (isset($gitem[2]) ? $gitem[2] : null);
                    $gval = isset($gitem['value']) ? $gitem['value'] : (isset($gitem[0]) ? $gitem[0] : null);
                    if ($gval === null) {
                        $gval = $gtext;
                    }
                    $selected = strval($gval) == strval($value) ? ' selected="selected"' : '';
                    $out[] = '  <option value="' . htmlspecialchars($gval) . '"' . $selected . '>';
                    $out[] = htmlspecialchars($gtext);
                    if (!empty($gtips)) {
                        $out[] = ' | ' . htmlspecialchars($gtips);
                    }
                    $out[] = '</option>' . "\n";
                }
                $out[] = '</optgroup>' . "\n";
                continue;
            }
            $selected = strval($val) == strval($value) ? ' selected="selected"' : '';
            $out[] = '<option value="' . htmlspecialchars($val) . '"' . $selected . '>';
            $out[] = htmlspecialchars($text);
            if (!empty($tips)) {
                $out[] = ' | ' . htmlspecialchars($tips);
            }
            $out[] = '</option>' . "\n";
        }
        $out[] = '</select>';
        return join('', $out);
    }

}