<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace beacon\widget;


use beacon\Field;

/**
 * 选项是延迟js设置的
 * Class DelaySelect
 * @package beacon\widget
 */
class DelaySelect extends Hidden
{

    public function code(Field $field, $attr = [])
    {
        $value = isset($attr['value']) ? $attr['value'] : $field->value;
        $attr['value'] = '';
        $attr['type'] = '';
        $attr['yee-module'] = 'delay-select';
        $attr['data-value'] = $value;
        //选项头
        if ($field->header !== null) {
            $attr['data-header'] = $field->header;
        }
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        $out = [];
        $out[] = '<select ' . join(' ', $attr) . '>';
        if ($field->header !== null) {
            $attr['data-header'] = $field->header;
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
        $out[] = '</select>';
        return join('', $out);
    }

}