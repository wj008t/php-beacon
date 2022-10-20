<?php

namespace beacon\widget;

use beacon\core\App;
use beacon\core\Field;

#[\Attribute]
class Tinymce extends Field
{
    protected array $_attrs=[
        'class'=>'form-inp tinymce',
    ];

    public string $imagesUploadUrl = '';
    public string $typeMode = 'basic';
    public bool $statusbar = false;
    public bool $elementPath = false;

    public function setting(array $args): void
    {
        parent::setting($args);
        if (isset($args['imagesUploadUrl']) && is_string($args['imagesUploadUrl'])) {
            $this->imagesUploadUrl = $args['imagesUploadUrl'];
        }
        if (isset($args['typeMode']) && is_string($args['typeMode'])) {
            $this->typeMode = $args['typeMode'];
        }
        if (isset($args['statusbar']) && is_bool($args['statusbar'])) {
            $this->statusbar = $args['statusbar'];
        }
        if (isset($args['elementPath']) && is_bool($args['elementPath'])) {
            $this->elementPath = $args['elementPath'];
        }
    }

    protected function code(array $attrs = []): string
    {
        if (!empty($this->imagesUploadUrl)) {
            $attrs['data-images-upload-url'] = App::url($this->imagesUploadUrl);
        }
        if (!empty($this->typeMode)) {
            $attrs['data-type-mode'] = $this->typeMode;
        }
        $attrs['data-statusbar'] = $this->statusbar ? 'true' : 'false';
        $attrs['data-elementpath'] = $this->elementPath ? 'true' : 'false';
        $attrs['yee-module'] = $this->getYeeModule('tinymce');
        return static::makeTag('textarea', ['attrs' => $attrs, 'exclude' => ['value'], 'text' => $attrs['value']]);
    }
}