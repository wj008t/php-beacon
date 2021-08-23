<?php


namespace beacon\widget;


use beacon\core\Field;
#[\Attribute]
class Hidden extends Field
{
    protected array $_attrs=[
        'class'=>'',
    ];

    protected function code(array $attrs = []): string
    {
        $attrs['type'] = 'hidden';
        return static::makeTag('input', ['attrs' => $attrs]);
    }

}