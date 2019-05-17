<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-12-3
 * Time: 上午3:35
 */

namespace beacon\widget;


use beacon\Field;

class UpFile extends Hidden
{
    public function code(Field $field, $attr = [])
    {
        $attr['yee-module'] = 'upload';
        $attr['type'] = 'text';
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $input)
    {
        if ($field->varType != 'array') {
            $field->varType = 'string';
        }
        return parent::assign($field, $input);
    }
}