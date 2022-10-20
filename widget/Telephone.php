<?php


namespace beacon\widget;
// array_is_list
if (!function_exists('array_is_list')) {
    function array_is_list(array $a)
    {
        return $a === [] || (array_keys($a) === range(0, count($a) - 1));
    }
}

use beacon\core\Field;
use beacon\core\Form;
use beacon\core\Request;

#[\Attribute]
class Telephone extends Field
{

    protected array $_attrs = [
        'class'=>'form-inp telephone',
    ];

    public ?array $names = null;
    public int $gWidth = 0;
    public int $qWidth = 0;
    public int $fWidth = 0;

    public int $mode = 2;

    public function setting(array $args): void
    {
        parent::setting($args);
        if (isset($args['names']) && is_array($args['names'])) {
            $this->names = $args['names'];
        }
        if (isset($args['mode']) && is_int($args['mode'])) {
            $this->mode = $args['mode'];
        }
        if (isset($args['gw']) && is_int($args['gw'])) {
            $this->gWidth = $args['gw'];
        }
        if (isset($args['qw']) && is_int($args['qw'])) {
            $this->qWidth = $args['qw'];
        }
        if (isset($args['fw']) && is_int($args['fw'])) {
            $this->fWidth = $args['fw'];
        }
    }

    public function init(?Form $form, string $name, string $type, mixed $default): void
    {
        parent::init($form, $name, $type, $default);
        if (empty($this->names)) {
            $this->names = [];
        }
        $names=array_is_list($this->names)?$this->names:array_keys($this->names);
        if ($this->mode == 1 && !isset($names[0])) {
            $names[0] = $this->boxName;
        } else if ($this->mode == 2 && !isset($names[1])) {
            $names[0] = $this->boxName . '_qh';
            $names[1] = $this->boxName;
        } else if ($this->mode == 3 && !isset($names[2])) {
            $names[0] = $this->boxName . '_qh';
            $names[1] = $this->boxName;
            $names[2] = $this->boxName . '_fj';
        } else if ($this->mode == 4 && !isset($names[2])) {
            $names[0] = $this->boxName . '_gh';
            $names[1] = $this->boxName . '_qh';
            $names[2] = $this->boxName;
        } else if ($this->mode == 5 && !isset($names[3])) {
            $names[0] = $this->boxName . '_gh';
            $names[1] = $this->boxName . '_qh';
            $names[2] = $this->boxName;
            $names[3] = $this->boxName . '_fj';
        }
    }

    private function getValues(): array
    {
        $value = $this->getValue();
        $phone = [];
        if ($this->mode == 1) {
            $phone['no'] = $value;
            return $phone;
        } else if ($this->mode == 2) {
            $m1 = explode('-', $value);
            $phone['qh'] = $m1[0] ?? '';
            $phone['no'] = $m1[1] ?? '';
            return $phone;
        } else if ($this->mode == 3) {
            $m1 = explode('-', $value);
            $phone['qh'] = $m1[0] ?? '';
            $phone['no'] = $m1[1] ?? '';
            $phone['fj'] = $m1[2] ?? '';
            return $phone;
        } else if ($this->mode == 4) {
            $m1 = explode(' ', $value);
            $phone['gh'] = $m1[0] ?? '';
            $value = $m1[1] ?? '';
            $m2 = explode('-', $value);
            $phone['qh'] = $m2[0] ?? '';
            $phone['no'] = $m2[1] ?? '';
            return $phone;
        } else if ($this->mode == 5) {
            $m1 = explode(' ', $value);
            $phone['gh'] = $m1[0] ?? '';
            $value = $m1[1] ?? '';
            $m2 = explode('-', $value);
            $phone['qh'] = $m2[0] ?? '';
            $phone['no'] = $m2[1] ?? '';
            $phone['fj'] = $m2[2] ?? '';
            return $phone;
        }
        return [];
    }

    private function getGroup(array $group, mixed $def = null): array
    {
        $phone = [];
        if ($this->mode == 1) {
            $phone['no'] = $group[0] ?? $def;
            return $phone;
        } else if ($this->mode == 2) {
            $phone = [];
            $phone['qh'] = $group[0] ?? $def;
            $phone['no'] = $group[1] ?? $def;
            return $phone;
        } else if ($this->mode == 3) {
            $phone = [];
            $phone['qh'] = $group[0] ?? $def;
            $phone['no'] = $group[1] ?? $def;
            $phone['fj'] = $group[2] ?? $def;
            return $phone;
        } else if ($this->mode == 4) {
            $phone['gh'] = $group[0] ?? $def;
            $phone['qh'] = $group[1] ?? $def;
            $phone['no'] = $group[2] ?? $def;
            return $phone;
        } else if ($this->mode == 5) {
            $phone['gh'] = $group[0] ?? $def;
            $phone['qh'] = $group[1] ?? $def;
            $phone['no'] = $group[2] ?? $def;
            $phone['fj'] = $group[3] ?? $def;
            return $phone;
        }
        return [];
    }

    protected function code(array $attrs = []): string
    {
        $phone = $this->getValues();
        $validGroup = $this->valid['group'] ?? [];
        $placeholder = explode(',', $attrs['placeholder'] ?? '');
        $validGroup = $this->getGroup($validGroup);
        $placeholder = $this->getGroup($placeholder, '');
        $widths = [];
        $widths['gh'] = empty($this->gWidth) ? 25 : $this->gWidth;
        $widths['qh'] = empty($this->qWidth) ? 35 : $this->qWidth;
        $widths['fj'] = empty($this->fWidth) ? 35 : $this->fWidth;

        $tNames=array_is_list($this->names)?$this->names:array_keys($this->names);
        $names = $this->getGroup($tNames, '');
//        Logger::log($phone, $validGroup, $placeholder, $names);

        unset($attrs['placeholder']);
        unset($attrs['data-valid-rule']);
        $style = '';
        $oldStyle = '';
        $out = [];
        if (!empty($attrs['style'])) {
            $style = $oldStyle = trim($attrs['style']);
            $style = rtrim($style, ';') . ';';
            $style = preg_replace('@width\s*:\s*\w+\s*;@i', '', $style);
        }
        //国区号
        if ($this->mode == 4 || $this->mode == 5) {
            $out[] = $this->makeInput($attrs, $style . 'width:' . $widths['gh'] . 'px;', $phone['gh'], $names['gh'], $placeholder['gh'], $validGroup['gh']);
        }
        if ($this->mode >= 2) {
            $out[] = $this->makeInput($attrs, $style . 'width:' . $widths['qh'] . 'px;', $phone['qh'], $names['qh'], $placeholder['qh'], $validGroup['qh']);
        }
        $out[] = $this->makeInput($attrs, $oldStyle, $phone['no'], $names['no'], $placeholder['no'], $validGroup['no']);

        if ($this->mode == 5 || $this->mode == 3) {
            $out[] = $this->makeInput($attrs, $style . 'width:' . $widths['fj'] . 'px;', $phone['fj'], $names['fj'], $placeholder['fj'], $validGroup['fj']);
        }
        return join("\n", $out);
    }

    /**
     * 生成输入框
     * @param array $attrs
     * @param string $style
     * @param mixed $value
     * @param string $name
     * @param string $placeholder
     * @param mixed|null $rule
     * @return string
     */
    protected function makeInput(array $attrs, string $style, mixed $value, string $name, string $placeholder, mixed $rule = null): string
    {
        $temp = [];
        $temp['style'] = $style;
        $temp['value'] = $value;
        $temp['name'] = $name;
        $temp['id'] = $temp['name'];
        if (!empty($placeholder)) {
            $temp['placeholder'] = $placeholder;
        }
        if ($rule != null) {
            $temp['data-valid-rule'] = $rule;
        }
        $temp = array_merge($attrs, $temp);
        return static::makeTag('input', ['attrs' => $temp]);
    }

    /**
     * 从参数中解析
     * @param array $param
     * @return mixed
     */
    public function fromParam(array $param = []): mixed
    {
        $tNames=array_is_list($this->names)?$this->names:array_keys($this->names);
        $names = $this->getGroup($tNames, '');
        $values = [];
        foreach ($names as $key => $name) {
            $values[$key] = Request::lookType($param, $name, 'string');
        }
        if ($this->mode == 1) {
            return $values['no'];
        } else if ($this->mode == 2) {
            if (!empty($values['qh']) && !empty($values['no'])) {
                return $values['qh'] . '-' . $values['no'];
            }
        } else if ($this->mode == 3) {
            if (!empty($values['qh']) && !empty($values['no'])) {
                if (empty($values['fj'])) {
                    return $values['qh'] . '-' . $values['no'];
                }
                return $values['qh'] . '-' . $values['no'] . '-' . $values['fj'];
            }
        } else if ($this->mode == 4) {
            if (!empty($values['gh']) && !empty($values['qh']) && !empty($values['no'])) {
                return $values['gh'] . ' ' . $values['qh'] . '-' . $values['no'];
            }
        } else if ($this->mode == 5) {
            if (!empty($values['gh']) && !empty($values['qh']) && !empty($values['no'])) {
                if (empty($values['fj'])) {
                    return $values['gh'] . ' ' . $values['qh'] . '-' . $values['no'];
                }
                return $values['gh'] . ' ' . $values['qh'] . '-' . $values['no'] . '-' . $values['fj'];
            }
        }
        return '';
    }
}