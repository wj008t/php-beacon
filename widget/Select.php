<?php


namespace beacon\widget;


use beacon\core\App;
use beacon\core\DB;
use beacon\core\DBException;
use beacon\core\Field;

#[\Attribute]
class Select extends Field
{
    public string|array $header = '';
    public array $options = [];
    public string|array $optionFunc = '';
    public string $optionSql = '';
    private ?array $cacheOptions = null;


    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['header']) && (is_string($args['header']) || is_array($args['header']))) {
            $this->header = $args['header'];
        }
        if (isset($args['optionFunc']) && (is_string($args['optionFunc']) || is_array($args['optionFunc']))) {
            $this->optionFunc = $args['optionFunc'];
        }
        if (isset($args['optionSql']) && is_string($args['optionSql'])) {
            $this->optionSql = $args['optionSql'];
        }
        if (isset($args['options']) && is_array($args['options'])) {
            $this->options = $args['options'];
        }
    }

    /**
     * 获取选项值
     * @param string $value
     * @return array
     * @throws DBException
     */
    private function getOptions(string $value): array
    {
        if ($this->cacheOptions !== null) {
            return $this->cacheOptions;
        }
        $options = $this->options;
        if (!empty($this->optionFunc) && is_callable($this->optionFunc)) {
            $options = array_merge($options, call_user_func($this->optionFunc));
        }
        if (!empty($this->optionSql)) {
            $list = DB::getList($this->optionSql);
            foreach ($list as $item) {
                if (isset($item['value'])) {
                    $options[] = $item;
                } else {
                    $options[] = array_values($item);
                }
            }
        }

        $this->cacheOptions = [];
        if (!empty($this->header)) {
            if (is_string($this->header)) {
                $this->cacheOptions[] = ['value' => '', 'text' => $this->header];
            } else if (isset($this->header['text'])) {
                $this->cacheOptions[] = ['value' => isset($this->header['value']) ? $this->header['value'] : $this->header['text'], 'text' => $this->header['text']];
            } else if (isset($this->header[0])) {
                $this->cacheOptions[] = ['value' => $this->header[0], 'text' => isset($this->header[1]) ? $this->header[1] : $this->header[0]];
            }
        }
        foreach ($options as $item) {
            if (is_array($item)) {
                if (isset($item['value']) && isset($item['text'])) {
                    $option = $item;
                } else if (isset($item[0])) {
                    $option = ['value' => $item[0], 'text' => $item[0]];
                    if (isset($item[1])) {
                        $option['text'] = $item[1];
                    }
                } else {
                    continue;
                }
            } else {
                $option = ['value' => $item, 'text' => $item];
            }
            if ($value == strval($option['value'])) {
                $option['selected'] = 'selected';
            }
            if ($option['data-url']) {
                $option['data-url'] = App::url($option['data-url']);
            }
            $this->cacheOptions[] = $option;
        }
        return $this->cacheOptions;
    }

    /**
     * 获取代码
     * @param array $attrs
     * @return string
     * @throws DBException
     */
    protected function code(array $attrs = []): string
    {
        $value = strval($attrs['value']);
        $options = $this->getOptions($value);
        $code = [];
        foreach ($options as $item) {
            $code[] = static::makeTag('option', ['attrs' => $item, 'exclude' => ['text'], 'text' => $item['text'], 'filter' => false]);
        }
        $optCode = "\n" . join("\n", $code) . "\n";
        return static::makeTag('select', ['attrs' => $attrs, 'exclude' => ['value'], 'code' => $optCode]);

    }


}