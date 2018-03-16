<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/2/13
 * Time: 2:01
 */

namespace  beacon\widget;


use beacon\Field;

class DynamicSelect extends Hidden
{
    public function code(Field $field, $args)
    {
        if (isset($args['value'])) {
            $field->value = $args['value'];
        }

        $options = isset($args['@options']) ? $args['@options'] : $field->options;
        $options = $options == null ? [] : $options;
        $args['value'] = '';
        $args['type'] = '';
        $args['yee-module'] = 'dynamic_select';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        $out = [];
        $out[] = '<select ' . join(' ', $attr);
        if ($field->header) {
            if (is_string($field->header)) {
                $out[] = ' data-header="' . htmlspecialchars($field->header) . '"';
            } else {
                $out[] = ' data-header="' . htmlspecialchars(json_encode($field->header, JSON_UNESCAPED_UNICODE)) . '"';
            }
        }
        if (!empty($options)) {
            $out[] = ' data-header="' . htmlspecialchars(json_encode($options, JSON_UNESCAPED_UNICODE)) . '"';
        }
        if ($field->_value !== null && $field->_value !== '') {
            $out[] = ' data-value="' . htmlspecialchars($field->_value) . '"';
        }
        $out[] = '></select>';
        return join('', $out);
    }

}