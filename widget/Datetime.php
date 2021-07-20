<?php


namespace beacon\widget;


use beacon\core\Field;
use beacon\core\Request;
use beacon\core\Util;
use beacon\core\Validator;

#[\Attribute]
class Datetime extends Field
{
    protected string $origValue = '';

    /**
     * 生成代码
     * @param array $attrs
     * @return string
     */
    protected function code(array $attrs = []): string
    {
        $attrs['yee-module'] = $this->getYeeModule('picker');
        $attrs['data-use-time'] = true;
        $attrs['type'] = 'text';
        $typeMap = Util::typeMap($this->varType);
        if (isset($typeMap['int']) && is_numeric($attrs['value'])) {
            if (isset($attrs['value']) && $attrs['value'] > 0) {
                $attrs['value'] = date('Y-m-d H:i:s', $attrs['value']);
            } else {
                unset($attrs['value']);
            }
        }
        return static::makeTag('input', ['attrs' => $attrs]);
    }

    /**
     * 从参数获取
     * @param array $param
     * @return int|string
     */
    public function fromParam(array $param = []): int|string
    {
        $value = Request::lookType($param, $this->boxName, 'string', '');
        $this->origValue = $value;
        $typeMap = Util::typeMap($this->varType);
        if (empty($value) || !Validator::testDate($value)) {
            if (isset($typeMap['int'])) {
                return 0;
            }
            return '';
        }
        if (isset($typeMap['int'])) {
            return strtotime($value);
        }
        return $value;
    }

    /**
     * 加入数据
     * @param array $data
     */
    public function joinData(array &$data = [])
    {
        $value = $this->getValue();
        if ($value === '') {
            $data[$this->name] = null;
        } else {
            $data[$this->name] = $value;
        }
    }

    /**
     * 验证控件
     * @param array $errors
     * @return bool
     */
    public function validate(array &$errors): bool
    {
        if (!empty($field->error)) {
            $errors[$this->name] = $field->error;
            return false;
        }
        if (empty($this->form) || $this->close || ($this->offEdit && $this->form->type == 'edit')) {
            return true;
        }
        //判断类型
        $typeMap = Util::typeMap($this->varType);
        if (isset($typeMap['int'])) {
            $ret = Validator::checkValue($this->origValue, $this->valid, $error);
            if (!$ret) {
                $errors[$this->name] = $this->error = $error;
                return false;
            }
            return true;
        }
        $ret = Validator::checkValue($this->getValue(), $this->valid, $error);
        if (!$ret) {
            $errors[$this->name] = $this->error = $error;
            return false;
        }
        return true;
    }

}