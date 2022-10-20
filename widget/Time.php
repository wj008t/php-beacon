<?php


namespace beacon\widget;


use beacon\core\Field;
use beacon\core\Request;
use beacon\core\Validator;

#[\Attribute]
class Time extends Field
{
    protected array $_attrs = [
        'class' => 'form-inp select',
    ];

    /**
     * 生成代码
     * @param array $attrs
     * @return string
     */
    protected function code(array $attrs = []): string
    {
        $values = explode(':', $attrs['value']);
        $vHour = intval($values[0] ?? 0);
        $vMinute = intval($values[1] ?? 0);
        $code = [];
        $hCode = [];
        $name = $attrs['name'];
        for ($i = 0; $i < 24; $i++) {
            $option = ['value' => $i, 'text' => str_pad($i, 2, '0', STR_PAD_LEFT)];
            $text = str_pad($i, 2, '0', STR_PAD_LEFT) . '时';
            if (strval($vHour) == strval($i)) {
                $option['selected'] = 'selected';
            }
            $hCode[] = static::makeTag('option', ['attrs' => $option, 'text' => $text, 'filter' => false]);
        }
        $optCode = "\n" . join("\n", $hCode) . "\n";
        $attrs['name'] = $name . '_hour';
        $code[] = static::makeTag('select', ['attrs' => $attrs, 'exclude' => ['value'], 'code' => $optCode]);

        $mCode = [];
        for ($i = 0; $i < 60; $i++) {
            $option = ['value' => $i, 'text' => str_pad($i, 2, '0', STR_PAD_LEFT)];
            $text = str_pad($i, 2, '0', STR_PAD_LEFT) . '分';
            if (strval($vMinute) == strval($i)) {
                $option['selected'] = 'selected';
            }
            $mCode[] = static::makeTag('option', ['attrs' => $option, 'text' => $text, 'filter' => false]);
        }
        $optCode = "\n" . join("\n", $mCode) . "\n";
        $attrs['name'] = $name . '_minute';
        $code[] = static::makeTag('select', ['attrs' => $attrs, 'exclude' => ['value'], 'code' => $optCode]);
        return join("\n", $code);
    }

    /**
     * 从参数获取
     * @param array $param
     * @return int|string
     */
    public function fromParam(array $param = []): int|string
    {
        $name = $this->boxName;
        $hName = $name . '_hour';
        $mName = $name . '_minute';
        $hValue = Request::lookType($param, $hName, 'string', '');
        $mValue = Request::lookType($param, $mName, 'string', '');
        if (strval($hValue) === '' || strval($mValue) === '' || !Validator::testInteger($hValue) || !Validator::testInteger($mValue)) {
            return '';
        }
        if (intval($hValue) < 0 || intval($hValue) > 24) {
            return '';
        }
        if (intval($mValue) < 0 || intval($mValue) > 60) {
            return '';
        }
        $hValue = str_pad($hValue, 2, '0', STR_PAD_LEFT);
        $mValue = str_pad($mValue, 2, '0', STR_PAD_LEFT);
        return $hValue . ':' . $mValue . ':00';
    }

    /**
     * 加入数据
     * @param array $data
     */
    public function joinData(array &$data = []): void
    {
        $value = $this->getValue();
        if ($value === '') {
            $data[$this->name] = null;
        } else {
            $data[$this->name] = $value;
        }
    }

}