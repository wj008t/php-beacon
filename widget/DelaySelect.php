<?php


namespace beacon\widget;


use beacon\core\App;
use beacon\core\Field;

#[\Attribute]
class DelaySelect extends Field
{
    public string|array $header = '';
    public string|array $source = '';
    public string $method = 'get';

    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['header']) && (is_string($args['header']) || is_array($args['header']))) {
            $this->header = $args['header'];
        }
        if (isset($args['source']) && (is_string($args['source']) || is_array($args['source']))) {
            $this->source = $args['source'];
        }
        if (isset($args['method']) && is_string($args['method'])) {
            $this->method = $args['method'];
        }
    }

    protected function code(array $attrs = []): string
    {
        $attrs['yee-module'] = $this->getYeeModule('delay-select');
        $attrs['data-value'] = $attrs['value'];
        $attrs['data-source'] = is_string($this->source) ? App::url($this->source) : $this->source;
        $attrs['data-method'] = $this->method;
        unset($attrs['value']);
        $header = [];
        if (!empty($this->header)) {
            if (is_string($this->header)) {
                $header = ['value' => '', 'text' => $this->header];
            } else if (isset($this->header['text'])) {
                $header = ['value' => $this->header['value'] ?? $this->header['text'], 'text' => $this->header['text']];
            } else if (isset($this->header[0])) {
                $header = ['value' => $this->header[0], 'text' => $this->header[1] ?? $this->header[0]];
            }
        }
        $attrs['data-header'] = $header;
        return static::makeTag('select', ['attrs' => $attrs, 'exclude' => ['value']]);
    }
}