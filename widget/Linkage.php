<?php


namespace beacon\widget;


use beacon\core\App;
use beacon\core\Field;
use beacon\core\Logger;
use beacon\core\Request;
use beacon\core\Util;

#[\Attribute]
class Linkage extends Field
{

    public ?array $names = null;
    public array $headers = [];
    public string $source = '';
    public string $method = 'get';
    public string $itemType = 'string';
    public int $level = 0;

    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['source']) && is_string($args['source'])) {
            $this->source = $args['source'];
        }
        if (isset($args['method']) && is_string($args['method'])) {
            $this->method = $args['method'];
        }
        if (isset($args['headers']) && is_array($args['headers'])) {
            $this->headers = $args['headers'];
        }
        if (isset($args['names']) && is_array($args['names'])) {
            $this->names = $args['names'];
        }
        if (isset($args['itemType']) && is_string($args['itemType'])) {
            $this->itemType = $args['itemType'];
        }
        if (isset($args['level']) && is_int($args['level'])) {
            $this->level = $args['level'];
        }
    }

    public function setValue(mixed $value)
    {
        if (empty($value)) {
            $this->value = [];
            return;
        }
        $this->value = Util::convertType($value, 'array');
    }

    private function getValues(bool $toStr = false): array
    {
        $values = $this->getValue();
        if (is_string($values) && Util::isJson($values)) {
            $values = json_decode($values, true);
        }
        if (!is_array($values)) {
            $values = [];
        }
        if ($toStr) {
            $values = array_map(function ($v) {
                return strval($v);
            }, $values);
        }
        return $values;
    }

    protected function code(array $attrs = []): string
    {
        $values = $this->getValues(true);
        $attrs['type'] = 'hidden';
        $attrs['value'] = '';
        if (count($values) > 0) {
            $attrs['value'] = $values;
        }
        $validGroup = $this->valid['group'] ?? [];

        if (!empty($this->headers)) {
            foreach ($this->headers as $idx => $header) {
                $level = $idx + 1;
                $attrs['data-header' . $level] = $header;
            }
        }

        if (!empty($this->names)) {
            foreach ($this->names as $idx => $name) {
                $level = $idx + 1;
                $attrs['data-name' . $level] = $name;
            }
        }

        if (!empty($validGroup)) {
            foreach ($validGroup as $idx => $valid) {
                $level = $idx + 1;
                $attrs['data-valid-rule' . $level] = $validGroup[$idx];
            }
        }

        $attrs['data-source'] = App::url($this->source);
        $attrs['data-method'] = $this->method;
        $attrs['yee-module'] = $this->getYeeModule('linkage');
        if ($this->level > 0) {
            $attrs['data-level'] = $this->level;
        }
        return static::makeTag('input', ['attrs' => $attrs]);
    }

    public function fromParam(array $param = []): array
    {
        if (!empty($this->names)) {
            $values = [];
            foreach ($this->names as $idx => $name) {
                if (empty($name)) {
                    continue;
                }
                $values[] = Request::lookType($param, $name, $this->itemType);
            }
            return $values;
        }
        $boxName = $this->boxName;
        $values = Request::lookType($param, $boxName, 'array');
        return Util::mapItemType($values, $this->itemType);
    }

    public function joinData(array &$data = [])
    {
        if (!empty($this->names)) {
            $values = $this->getValues();

            foreach ($this->names as $idx => $name) {
                if (empty($name)) {
                    continue;
                }
                $data[$name] = 0;
                $value = isset($values[$idx]) ? $values[$idx] : null;
                $data[$name] = Util::convertType($value, $this->itemType);
            }
            return;
        }
        $values = $this->getValues();
        $data[$this->name] = Util::mapItemType($values, $this->itemType);;
    }

    public function fromData(array $data = []): array
    {
        if (!empty($this->names)) {
            $values = [];
            foreach ($this->names as $idx => $name) {
                if (empty($name)) {
                    continue;
                }
                $value = isset($data[$name]) ? $data[$name] : null;
                $values[] = Util::convertType($value, $this->itemType);
            }
            return $values;
        }
        $values = isset($data[$this->name]) ? $data[$this->name] : '';
        if (is_string($values) && Util::isJson($values)) {
            $values = json_decode($values, true);
        }
        if (is_array($values)) {
            return Util::mapItemType($values, $this->itemType);
        }
        return [];
    }


}