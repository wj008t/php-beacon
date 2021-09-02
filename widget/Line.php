<?php


namespace beacon\widget;


use beacon\core\Field;

#[\Attribute]
class Line extends Field
{
    protected array $_attrs=[
        'class'=>'form-line',
    ];

    public bool $offJoin = true;

    public function setting(array $args)
    {
        parent::setting($args);
        $this->offJoin = true;
    }

    protected function code(array $attrs = []): string
    {
        return '';
    }

    public function fromParam(array $param = []): mixed
    {
        return '';
    }

    public function joinData(array &$data = [])
    {

    }
}