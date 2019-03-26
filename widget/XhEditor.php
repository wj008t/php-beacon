<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-12-4
 * Time: 上午4:44
 */

namespace beacon\widget;


use beacon\Field;

class XhEditor extends Hidden
{

    public function code(Field $field, $attr = [])
    {
        $attr['yee-module'] = 'xh-editor';
        $attr['type'] = '';
        if (isset($attr['value'])) {
            $field->value = $attr['value'];
        }
        $args['value'] = '';
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<textarea ' . join(' ', $attr) . '>' . htmlspecialchars($field->value) . '</textarea>';
    }

    public function assign(Field $field, array $input)
    {
        $field->varType = 'string';
        return parent::assign($field, $input);
    }

}