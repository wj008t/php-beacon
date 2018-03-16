<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace  beacon\widget;


use beacon\Field;

class Markdown extends Hidden
{

    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'markdown';
        if (isset($args['value'])) {
            $field->value = $args['value'];
        }
        $args['type'] = '';
        $args['value'] = '';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<textarea ' . join(' ', $attr) . '>' . htmlspecialchars($field->value) . '</textarea>';
    }

    public function assign(Field $field, array $data)
    {
        $field->varType = 'string';
        return parent::assign($field, $data);
    }
}