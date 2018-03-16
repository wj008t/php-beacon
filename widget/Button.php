<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace  beacon\widget;


use beacon\Field;

class Button implements BoxInterface
{
    public function code(Field $field, $args)
    {
        $args['type'] = '';
        $args['name'] = '';
        if (empty($args['href'])) {
            $args['href'] = $field->boxHref == null ? 'javascript:;' : $field->boxHref;
        }
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);

        return '<a ' . join(' ', $attr) . ' >' . htmlspecialchars($field->label) . '</a>';
    }

    public function assign(Field $field, array $data)
    {

    }

    public function fill(Field $field, array &$values)
    {

    }

    public function init(Field $field, array $values)
    {

    }
}