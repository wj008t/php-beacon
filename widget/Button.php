<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace beacon\widget;


use beacon\Field;

class Button implements WidgetInterface
{
    public function code(Field $field, $attr = [])
    {
        $attr['type'] = '';
        $attr['name'] = '';
        if (empty($attr['href'])) {
            $attr['href'] = $field->boxHref == null ? 'javascript:;' : $field->boxHref;
        }
        $attr = WidgetHelper::mergeAttributes($field, $attr);
        return '<a ' . join(' ', $attr) . ' >' . htmlspecialchars($field->label) . '</a>';
    }

    public function assign(Field $field, array $input)
    {

    }

    public function fill(Field $field, array &$values)
    {

    }

    public function init(Field $field, array $values)
    {

    }
}