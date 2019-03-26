<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-11-27
 * Time: 下午11:35
 */

namespace beacon\widget;


use beacon\Field;

class SelectDialog extends Hidden
{

    public function code(Field $field, $attr = [])
    {
        $attr['type'] = 'hidden';
        $attr['yee-module'] = 'select-dialog';
        if (!empty($field->value)) {
            $textFunc = $field->getFunc('text');
            if ($textFunc && is_callable($textFunc)) {
                $value = call_user_func($textFunc, $field->value);
                $attr['data-text'] = $value;
            } else {
                $attr['data-text'] = $field->value;
            }
        }
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<input ' . join(' ', $attr) . ' />';
    }
}