<?php


namespace beacon\widget;

use beacon\core\DB;
use beacon\core\DBException;
use beacon\core\Field;
use beacon\core\Logger;
use beacon\core\Request;
use beacon\core\Util;


#[\Attribute]
class CheckGroup extends Field
{
    protected array $_attrs = [
        'class' => 'check-group',
        'inp-class' => 'form-inp'
    ];
    public array $options = [];
    public string|array $optionFunc = '';
    public string $optionSql = '';

    public bool $bitComp = false;

    public ?array $names = null;

    public string $itemType = 'string';

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
        if (isset($args['bitComp']) && is_bool($args['bitComp'])) {
            $this->bitComp = $args['bitComp'];
            if ($this->bitComp) {
                $this->itemType = 'int';
            }
        }
        if (isset($args['names']) && is_array($args['names'])) {
            $this->names = $args['names'];
        }
        if (isset($args['itemType']) && is_string($args['itemType'])) {
            $this->itemType = $args['itemType'];
        }
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

    private function isList(array $data)
    {
        if (function_exists('array_is_list')) {
            return \array_is_list($data);
        }
        return $a === [] || (array_keys($a) === range(0, count($a) - 1));
    }

    /**
     * 获取选项值
     * @param array $values
     * @return array
     * @throws DBException
     */
    private function getOptions(array $values = []): array
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
            $value = strval($option['value']);
            if (in_array($value, $values)) {
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
        $values = $this->getValues(true);
        $name = $this->boxName;
        $class = $attrs['class'] ?? '';
        $style = $attrs['style'] ?? '';

        $options = $this->getOptions($values);
        $code = [];
        $keys = array_keys($options);
        $endKey = end($keys);
        foreach ($options as $key => $item) {
            if ($item == null) {
                continue;
            }
            $text = $item['text'];
            $item['type'] = 'checkbox';
            $item['name'] = $name . '[]';
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

    /**
     * 从表单拿值
     * @param array $param
     * @return array
     */
    public function fromParam(array $param = []): array
    {
        $name = $this->boxName;
        $values = Request::lookType($param, $name, 'array');
        return Util::mapItemType($values, $this->itemType);
    }

    /**
     * 加入到数据中
     * @param array $data
     * @throws DBException
     */
    public function joinData(array &$data = [])
    {
        if ($this->bitComp) {
            $values = $this->getValues();
            $value = 0;
            foreach ($values as $item) {
                if ((is_string($item) || is_integer($item)) && preg_match('@^\d+$@', $item)) {
                    $value = $value | intval($item);
                }
            }
            $data[$this->name] = $value;
            return;
        }

        if (!empty($this->names)) {
            $values = $this->getValues(true);
            $options = $this->getOptions();
            $opts = [];
            foreach ($options as $item) {
                $opts[] = strval($item['value']);
            }
            $names = $this->isList($this->names) ? $this->names : array_keys($this->names);
            foreach ($names as $idx => $name) {
                $data[$name] = 0;
                $val = $opts[$idx] ?? null;
                if ($val !== null && in_array($val, $values)) {
                    $data[$name] = 1;
                }
            }
            return;
        }
        $values = $this->getValues();
        $data[$this->name] = Util::mapItemType($values, $this->itemType);
    }

    /**
     * @param array $data
     * @return array
     * @throws DBException
     */
    public function fromData(array $data = []): array
    {
        if ($this->bitComp) {
            $value = isset($data[$this->name]) ? intval($data[$this->name]) : 0;
            $values = [];
            $options = $this->getOptions();
            foreach ($options as $item) {
                $opt_value = intval($item['value']);
                $values[] = $value & $opt_value > 0 ? 1 : 0;
            }
            return $values;
        }
        if (!empty($this->names)) {
            $options = $this->getOptions();
            $values = [];
            $opts = [];
            foreach ($options as $item) {
                $opts[] = $item['value'];
            }
            if ($this->isList($this->names)) {
                $type = $this->itemType;
                foreach ($this->names as $idx => $name) {
                    $opt_value = $opts[$idx] ?? null;
                    if ($opt_value !== null && isset($data[$name]) && intval($data[$name]) == 1) {
                        if ($type == 'int' || $type == 'tinyint') {
                            $opt_value = intval($opt_value);
                        } else if ($type == 'decimal') {
                            $opt_value = floatval($opt_value);
                        } else {
                            $opt_value = strval($opt_value);
                        }
                        $values[] = $opt_value;
                    }
                }
            } else {
                $idx = 0;
                foreach ($this->names as $name => $type) {
                    $opt_value = $opts[$idx] ?? null;
                    if ($opt_value !== null && isset($data[$name]) && intval($data[$name]) == 1) {
                        if ($type == 'int' || $type == 'tinyint') {
                            $opt_value = intval($opt_value);
                        } else if ($type == 'decimal') {
                            $opt_value = floatval($opt_value);
                        } else {
                            $opt_value = strval($opt_value);
                        }
                        $values[] = $opt_value;
                    }
                    $idx++;
                }
            }
            return $values;
        }
        $values = $data[$this->name] ?? '';
        if (is_string($values) && Util::isJson($values)) {
            $values = json_decode($values, true);
        }
        if (is_array($values)) {
            return Util::mapItemType($values, $this->itemType);
        }
        return [];
    }

}