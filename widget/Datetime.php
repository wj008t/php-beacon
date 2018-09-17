<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 4:01
 */

namespace  beacon\widget;


use beacon\Field;
use beacon\Request;
use beacon\Validate;

class Datetime extends Hidden
{
    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'date';
        $args['data-type'] = 'datetime';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    /**
     * @param Field $field
     * @param array $data
     * @return array|mixed|null|void
     */
    public function assign(Field $field, array $data)
    {
        $val = Request::instance()->input($data, $field->boxName . ':s', '');
        if (!Validate::test_date($val)) {
            $field->value = null;
        }
        $field->value = $val;
    }

    public function fill(Field $field, array &$values)
    {
        if ($field->varType == 'int' || $field->varType == 'integer') {
            $values[$field->name] = strtotime($field->value);
            return;
        }
        $values[$field->name] = $field->value;
    }

    public function init(Field $field, array $values)
    {
        if ($field->varType == 'int' || $field->varType == 'integer') {
            $time = isset($values[$field->name]) ? $values[$field->name] : 0;
            $field->value = date('Y-m-d H:i:s', $time);
            return;
        }
        $field->value = isset($values[$field->name]) ? $values[$field->name] : null;
    }


}