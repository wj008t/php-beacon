<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-11-24
 * Time: 下午5:26
 */

namespace beacon\widget;


use beacon\Field;

/**
 * 需要远程校验的文本输入框
 * Class Remote
 * @package beacon\widget
 */
class Remote extends Hidden
{
    public function code(Field $field, $attr = [])
    {
        $attr['type'] = 'text';
        $attr['yee-module'] = 'remote';
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $input)
    {
        $field->varType = 'string';
        return parent::assign($field, $input);
    }
}