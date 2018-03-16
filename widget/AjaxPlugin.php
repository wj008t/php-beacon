<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace  beacon\widget;


use beacon\Console;
use beacon\Field;
use beacon\Form;
use beacon\Utils;

class AjaxPlugin implements BoxInterface
{


    public function code(Field $field, $args)
    {
        $args['type'] = '';
        $args['class'] = 'form-ajax-layout';
        $args['yee-module'] = 'ajax_plugin';
        $args['data-value'] = $field->_value;
        $args['value'] = '';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<div ' . join(' ', $attr) . ' ></div>';
    }

    public function assign(Field $field, array $data)
    {

    }

    public function fill(Field $field, array &$values)
    {

    }

    public function init(Field $field, array $values)
    {

        $temp = isset($values[$field->name]) ? $values[$field->name] : null;
        if (Utils::isJsonString($temp)) {
            $field->value = json_decode($temp, true);
        } else {

        }
    }
}