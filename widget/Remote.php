<?php


namespace beacon\widget;


use beacon\core\App;
use beacon\core\Field;

#[\Attribute]
class Remote extends Field
{
    public string $url = '';
    public string $method = 'get';
    public string $carry = '';

    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['url']) && is_string($args['url'])) {
            $this->url = $args['url'];
        }
        if (isset($args['method']) && is_string($args['method'])) {
            $this->method = $args['method'];
        }
        if (isset($args['carry']) && is_string($args['carry'])) {
            $this->carry = $args['carry'];
        }
    }

    protected function code(array $attrs = []): string
    {
        $attrs['yee-module'] = $this->getYeeModule('remote');
        $attrs['data-url'] = App::url($this->url);
        $attrs['data-method'] = $this->method;
        $attrs['data-carry'] = $this->carry;
        $attrs['type'] = 'text';
        return static::makeTag('input', ['attrs' => $attrs]);
    }
}