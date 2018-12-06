<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 3:36
 */

namespace beacon\widget;


use beacon\Field;

class Check extends Hidden
{

    public function code(Field $field, $attr = [])
    {
        $attr['checked'] = $field->value ? 'checked' : '';
        $attr['type'] = 'checkbox';
        $attr['value'] = 1;
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $input)
    {
        $field->varType = 'boolean';
        return parent::assign($field, $input);
    }

    public function fill(Field $field, array &$values)
    {
        $values[$field->name] = $field->value ? 1 : 0;
    }

    public function init(Field $field, array $values)
    {
        $field->value = isset($values[$field->name]) ? ($values[$field->name] == 1) : null;
    }
}