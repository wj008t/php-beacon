<?php

namespace beacon\widget;

use beacon\core\Field;

#[\Attribute]
class Textarea extends Field
{
    protected array $_attrs=[
        'class'=>'form-inp textarea',
    ];

    protected function code(array $attrs = []): string
    {
        return static::makeTag('textarea', ['attrs' => $attrs, 'exclude' => ['value'], 'text' => $attrs['value']]);
    }
}