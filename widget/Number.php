<?php


namespace beacon\widget;


use beacon\core\Field;

#[\Attribute]
class Number extends Field
{
    protected function code(array $attrs = []): string
    {
        $attrs['type'] = 'text';
        return static::makeTag('input', ['attrs' => $attrs]);
    }
}