<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace beacon\widget;


use beacon\Field;

class Password extends Hidden
{

    public function code(Field $field, $attr = [])
    {
        $attr['type'] = 'password';
        $attr['value'] = null;
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $input)
    {
        $field->varType = 'string';
        return parent::assign($field, $input);
    }

    public function fill(Field $field, array &$values)
    {
        $encodeFunc = $field->getFunc('encode');
        if ($field->value !== null && $field->value !== '' && $field->encodeValue !== $field->value && $encodeFunc !== null) {
            if (is_callable($encodeFunc)) {
                $field->encodeValue = call_user_func($encodeFunc, $field->value);
                $values[$field->name] = $field->encodeValue;
                return;
            }
        }
        if (($field->value == null || $field->value == '') && $field->encodeValue !== null && $field->encodeValue !== '') {
            return;
        }
        $values[$field->name] = $field->value;
    }

    public function init(Field $field, array $values)
    {
        $field->value = isset($values[$field->name]) ? $values[$field->name] : null;
        $field->encodeValue = $field->value;
    }

}