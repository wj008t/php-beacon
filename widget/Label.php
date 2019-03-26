<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-11-28
 * Time: 下午11:32
 */

namespace beacon\widget;


use beacon\Field;

class Label implements WidgetInterface
{

    public function code(Field $field, $attr = [])
    {
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<span ' . join(' ', $attr) . '>' . htmlspecialchars($field->value) . '</span>';
    }

    public function assign(Field $field, array $input)
    {
    }

    public function fill(Field $field, array &$values)
    {
    }

    public function init(Field $field, array $values)
    {
        $name = $field->name;
        $field->value = isset($values[$name]) ? $values[$name] : null;
    }
}