<?php

namespace beacon\widget;

use beacon\core\Field;

#[\Attribute]
class Text extends Field
{
    protected array $_attrs=[
        'class'=>'form-inp text',
    ];
    protected function code(array $attrs = []): string
    {
        $attrs['type'] = 'text';
        return static::makeTag('input', ['attrs' => $attrs]);
    }
}