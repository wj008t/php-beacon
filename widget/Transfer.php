<?php


namespace beacon\widget;


use beacon\core\App;
use beacon\core\Field;

/**
 * 穿梭框
 * Class Transfer
 * @package beacon\widget
 */
#[\Attribute]
class Transfer extends Field
{

    protected array $_attrs=[
        'class'=>'form-inp transfer',
    ];
    /**
     * 对话框链接
     * @var string
     */
    public string $source = '';
    public string $method = 'get';
    /**
     * @var string 选项标题
     */
    public string $caption = '';
    public int $width = 0;
    public int $height = 0;
    public bool $search = false;

    public function setting(array $args): void
    {
        parent::setting($args);
        if (isset($args['source']) && is_string($args['source'])) {
            $this->source = $args['source'];
        }
        if (isset($args['method']) && is_string($args['method'])) {
            $this->method = $args['method'];
        }
        if (isset($args['caption']) && is_string($args['caption'])) {
            $this->caption = $args['caption'];
        }
        if (isset($args['width']) && is_int($args['width'])) {
            $this->width = $args['width'];
        }
        if (isset($args['height']) && is_int($args['height'])) {
            $this->height = $args['height'];
        }
        if (isset($args['search']) && is_bool($args['search'])) {
            $this->search = $args['search'];
        }
    }

    protected function code(array $attrs = []): string
    {
        $attrs['yee-module'] = $this->getYeeModule('transfer');
        $attrs['type'] = 'hidden';
        $attrs['data-source'] = App::url($this->source);
        $attrs['data-method'] = $this->method;
        $attrs['data-caption'] = $this->caption;
        if ($this->search) {
            $attrs['data-search'] = 1;
        }
        if ($this->width > 0) {
            $attrs['data-width'] = $this->width;
        }
        if ($this->height > 0) {
            $attrs['data-height'] = $this->height;
        }
        return static::makeTag('input', ['attrs' => $attrs]);
    }
}