<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 4:01
 */

namespace beacon\widget;


use beacon\Field;

class Integer extends Hidden
{
    public function code(Field $field, $attr = [])
    {
        $attr['yee-module'] = 'integer';
        $attr['type'] = 'text';
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $input)
    {
        $field->varType = 'int';
        return parent::assign($field, $input);
    }

}