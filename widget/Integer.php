<?php


namespace beacon\widget;


use beacon\core\Field;

#[\Attribute]
class Integer extends Field
{
    protected array $_attrs=[
        'class'=>'form-inp integer',
    ];

    protected function code(array $attrs = []): string
    {
        $attrs['type'] = 'number';
        return static::makeTag('input', ['attrs' => $attrs]);
    }
}