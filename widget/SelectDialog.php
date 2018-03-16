<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/16
 * Time: 2:04
 */

namespace  beacon\widget;


use beacon\Console;
use beacon\Field;

class SelectDialog extends Hidden
{
    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'select_dialog';
        $args['type'] = 'text';
        if (!empty($field->value)) {
            if ($field->textFunc !== null && is_callable($field->textFunc)) {
                $args['data-text'] = call_user_func($field->textFunc, $field->value);
            } else {
                $args['data-text'] = $field->value;
            }
        }
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

}