<?php

namespace beacon\widget;

use beacon\core\App;
use beacon\core\Field;

#[\Attribute]
class UpFile extends Field
{
    public string $url = '/service/upload';
    public string $mode = 'file';
    public string $extensions = '';
    public string $fieldName = 'filedata';
    public string $nameInput = '';
    public int $size = 0;

    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['url']) && is_string($args['url'])) {
            $this->url = $args['url'];
        }
        if (isset($args['mode']) && is_string($args['mode'])) {
            $this->mode = $args['mode'];
        }
        if (isset($args['extensions']) && is_string($args['extensions'])) {
            $this->extensions = $args['extensions'];
        }
        if (isset($args['fieldName']) && is_string($args['fieldName'])) {
            $this->fieldName = $args['fieldName'];
        }
        if (isset($args['nameInput']) && is_string($args['nameInput'])) {
            $this->nameInput = $args['nameInput'];
        }
        if (isset($args['size']) && is_int($args['size'])) {
            $this->size = $args['size'];
        }
    }

    protected function code(array $attrs = []): string
    {
        $attrs['yee-module'] = $this->getYeeModule('upload');
        $attrs['type'] = 'text';
        $attrs['data-url'] = App::url($this->url);
        if (!empty($this->mode)) {
            $attrs['data-mode'] = $this->mode;
            if ($this->mode == 'fileGroup' && $this->size > 0) {
                $attrs['data-size'] = $this->size;
            }
            if ($this->mode == 'file' && !empty($this->nameInput)) {
                $attrs['data-name-input'] = $this->nameInput;
            }
        }
        if (!empty($this->extensions)) {
            $attrs['data-extensions'] = $this->extensions;
        }
        if (!empty($this->fieldName)) {
            $attrs['data-field-name'] = $this->fieldName;
        }
        return static::makeTag('input', ['attrs' => $attrs]);
    }
}