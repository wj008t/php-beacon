<?php


namespace beacon\widget;


use beacon\core\DB;
use beacon\core\DBException;
use beacon\core\Field;

#[\Attribute]
class RadioGroup extends Field
{
    protected array $_attrs=[
        'class'=>'radio-group',
        'inp-class'=>'form-inp',
    ];

    public array $options = [];
    public string|array $optionFunc = '';
    public string $optionSql = '';

    private ?array $cacheOptions = null;

    public function setting(array $args)
    {
        parent::setting($args);
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
                $option['checked'] = 'checked';
            }
            $this->cacheOptions[] = $option;
        }
        return $this->cacheOptions;
    }

    /**
     * @param array $attrs
     * @return string
     * @throws DBException
     */
    protected function code(array $attrs = []): string
    {
        $value = strval($attrs['value']);
        $name = $this->boxName;
        $class = $attrs['class'] ?? '';
        $style = $attrs['style'] ?? '';
        $options = $this->getOptions($value);
        $code = [];
        $keys = array_keys($options);
        $endKey = end($keys);
        foreach ($options as $key => $item) {
            if ($item == null) {
                continue;
            }
            $text = $item['text'];
            $item['type'] = 'radio';
            $item['name'] = $name;
            if (!empty($attrs['inp-class'])) {
                $item['class'] = $attrs['inp-class'];
            }
            if (!empty($attrs['disabled'])) {
                $item['disabled'] = $attrs['disabled'];
            }
            if (!empty($attrs['readonly'])) {
                $item['readonly'] = $attrs['readonly'];
            }
            $code[] = '<label';
            if (!empty($class)) {
                $code[] = ' class="' . $class . '"';
            }
            if (!empty($style)) {
                $code[] = ' style="' . $style . '"';
            }
            $code[] = '>';
            if ($endKey === $key) {
                foreach ($attrs as $aKey => $attr) {
                    if (preg_match('@^(data-|yee-)@', $aKey)) {
                        $item[$aKey] = $attr;
                    }
                }
            }
            $code[] = static::makeTag('input', ['attrs' => $item, 'exclude' => ['text', 'tips']]);

            $code[] = '<span>' . htmlspecialchars($text);
            if (!empty($item['tips'])) {
                $code[] = '<em>' . htmlspecialchars($item['tips']) . '</em>';
            }
            $code[] = '</span></label>' . "\n";
        }
        return join('', $code);

    }
}