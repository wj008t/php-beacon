<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace beacon\widget;


use beacon\Field;

class Tinymce extends Hidden
{

    public function code(Field $field, $attr = [])
    {
        $attr['yee-module'] = 'tinymce';
        if (isset($attr['value'])) {
            $field->value = $attr['value'];
        }
        $attr['type'] = '';
        $attr['value'] = '';
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<textarea ' . join(' ', $attr) . '>' . htmlspecialchars($field->value) . '</textarea>';
    }

    public function assign(Field $field, array $input)
    {
        $field->varType = 'string';
        return parent::assign($field, $input);
    }
}