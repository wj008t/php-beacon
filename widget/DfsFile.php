<?php


namespace beacon\widget;


use beacon\core\Config;
use beacon\core\Field;

#[\Attribute]
class DfsFile extends Field
{
    public string $module = 'go-dfs';
    public string $conf = 'dfs';
    public string $fieldName = 'file';

    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['module']) && is_string($args['module'])) {
            $this->module = $args['module'];
        }
        if (isset($args['conf']) && is_string($args['conf'])) {
            $this->conf = $args['conf'];
        }
        if (isset($args['fieldName']) && is_string($args['fieldName'])) {
            $this->fieldName = $args['fieldName'];
        }
    }

    protected function code(array $attrs = []): string
    {
        $this->addYeeModule('upload');
        $attrs['yee-module'] = $this->getYeeModule($this->module);
        $config = Config::get($this->conf);
        $attrs['data-url'] = $config['upload_url'] ?? '';
        $attrs['data-web-url'] = $config['web_url'] ?? '';
        if (isset($config['tokenFunc']) && is_callable($config['tokenFunc'])) {
            $token = call_user_func($config['tokenFunc']);
            $attrs['data-token'] = $token;
        }
        if (!empty($config['token'])) {
            $attrs['data-token'] = $config['token'];
        }
        if (isset($config['param'])) {
            $attrs['data-param'] = $config['param'];
        }
        if (isset($config['path'])) {
            $attrs['data-path'] = $config['path'];
        }
        $attrs['data-field-name'] = $this->fieldName;
        $attrs['type'] = 'text';
        return parent::code($attrs);
    }
}