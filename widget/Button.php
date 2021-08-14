<?php


namespace beacon\widget;


use beacon\core\Field;

#[\Attribute]
class Button extends Field
{
    protected array $_attrs=[
        'class'=>'form-btn'
    ];

    public bool $offJoin = true;

    public function setting(array $args)
    {
        parent::setting($args);
        $this->offJoin = true;
    }

    protected function code(array $attrs = []): string
    {
        if (empty($attrs['href'])) {
            $attrs['href'] = 'javascript:;';
        }
        unset($attrs['name']);
        return static::makeTag('a', ['attrs' => $attrs, 'text' => $this->label]);
    }

    public function fromParam(array $param = []): mixed
    {
        return $this->getValue();
    }

    public function fromData(array $data = []): mixed
    {
        return $this->getValue();
    }

    public function joinData(array &$data = [])
    {

    }

}