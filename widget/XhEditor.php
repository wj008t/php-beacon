<?php

namespace beacon\widget;

use beacon\core\App;
use beacon\core\Field;

#[\Attribute]
class XhEditor extends Field
{
    public string $upLinkUrl = '';
    public string $upLinkExt = '';
    public string $upImgUrl = '';
    public string $upImgExt = '';

    public string $skin = '';
    public string $tools = '';

    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['upLinkUrl']) && is_string($args['upLinkUrl'])) {
            $this->upLinkUrl = $args['upLinkUrl'];
        }
        if (isset($args['upLinkExt']) && is_string($args['upLinkExt'])) {
            $this->upLinkExt = $args['upLinkExt'];
        }
        if (isset($args['upImgUrl']) && is_string($args['upImgUrl'])) {
            $this->upImgUrl = $args['upImgUrl'];
        }
        if (isset($args['upImgExt']) && is_string($args['upImgExt'])) {
            $this->upImgExt = $args['upImgExt'];
        }
        if (isset($args['skin']) && is_string($args['skin'])) {
            $this->skin = $args['skin'];
        }
        if (isset($args['tools']) && is_string($args['tools'])) {
            $this->tools = $args['tools'];
        }
    }

    protected function code(array $attrs = []): string
    {
        if (!empty($this->upLinkUrl)) {
            $attrs['data-up-link-url'] = App::url($this->upLinkUrl);
        }
        if (!empty($this->upImgUrl)) {
            $attrs['data-up-img-url'] = App::url($this->upImgUrl);
        }
        if (!empty($this->upLinkExt)) {
            $attrs['data-up-link-ext'] = $this->upLinkExt;
        }
        if (!empty($this->upImgExt)) {
            $attrs['data-up-img-ext'] = $this->upImgExt;
        }
        if (!empty($this->skin)) {
            $attrs['data-skin'] = $this->skin;
        }
        if (!empty($this->tools)) {
            $attrs['data-tools'] = $this->tools;
        }
        $attrs['yee-module'] = $this->getYeeModule('xh-editor');
        return static::makeTag('textarea', ['attrs' => $attrs, 'exclude' => ['value'], 'text' => $attrs['value']]);
    }
}