<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace beacon\widget;

use beacon\Field;

class Hidden implements WidgetInterface
{
    public function code(Field $field, $attr = [])
    {
        $attr['type'] = 'hidden';
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $input)
    {
        $boxName = $field->boxName;
        $field->value = WidgetHelper::getValue($field->varType, $input, $boxName);
        return $field->value;
    }

    public function fill(Field $field, array &$values)
    {
        $values[$field->name] = $field->value;
    }

    public function init(Field $field, array $values)
    {
        $field->value = isset($values[$field->name]) ? $values[$field->name] : null;
    }
}