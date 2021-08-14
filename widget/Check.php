<?php


namespace beacon\widget;


use beacon\core\Field;

#[\Attribute]
class Check extends Field
{
    protected array $_attrs=[
        'class'=>'form-inp check'
    ];

    protected function code(array $attrs = []): string
    {
        $attrs['checked'] = $attrs['value'] ? 'checked' : '';
        unset($attrs['placeholder']);
        $attrs['type'] = 'checkbox';
        $attrs['value'] = 1;
        return static::makeTag('input', ['attrs' => $attrs]);
    }

    public function render(array $attrs = []): string
    {
        if ($this->form !== null) {
            $this->createDynamic();
        }
        $attrs = array_merge($this->attrs(), $attrs);
        $code = [];
        if (!empty($this->before)) {
            $code[] = '<span class="before"> ' . $this->before . '</span>';
        }
        $code[] = '<label>';
        $this->addDefaultAttr($attrs);
        $code[] = $this->code($attrs);
        if (!empty($this->after)) {
            $code[] = '<span class="after"> ' . $this->after . '</span>';
        }
        $code[] = '</label>';
        return join('', $code);
    }

}