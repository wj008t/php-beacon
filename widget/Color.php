<?php


namespace beacon\widget;


use beacon\core\Field;

#[\Attribute]
class Color extends Field
{

    protected array $_attrs=[
        'class'=>'form-inp color',
    ];

    protected function code(array $attrs = []): string
    {
        $attrs['yee-module'] = $this->getYeeModule('color');
        $attrs['type'] = 'text';
        return static::makeTag('input', ['attrs' => $attrs]);
    }
}