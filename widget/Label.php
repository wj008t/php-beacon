<?php


namespace beacon\widget;


use beacon\core\Field;

#[\Attribute]
class Label extends Field
{
    protected array $_attrs=[
        'class'=>'form-label',
    ];

    protected function code(array $attrs = []): string
    {
        return static::makeTag('span', ['attrs' => $attrs, 'exclude' => ['value'], 'text' => $attrs['value']]);
    }

    public function fromParam(array $param = []): mixed
    {
        return $this->getValue();
    }

    public function joinData(array &$data = [])
    {

    }
}