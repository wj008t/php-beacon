<?php

namespace beacon;

/**
 * Created by PhpStorm.
 * User=> wj008
 * Date: 2017/12/14
 * Time: 0:26
 */
class Validate
{
    /**
     * 默认消息
     * @var array
     */
    private static $default_errors = null;

    /**
     * 代理短写
     * @var array
     */
    private static $alias = [
        'r' => 'required',
        'i' => 'integer',
        'int' => 'integer',
        'num' => 'number',
        'minlen' => 'minlength',
        'maxlen' => 'maxlength',
        'eqto' => 'equalto',
        'eq' => 'equal',
        'neq' => 'notequal'
    ];

    private static $staticFunc = [];

    private $remoteFunc = [];
    private $func = [];
    private $def_errors = [];

    /**
     * 字符串格式化输出
     * @param string $str
     * @param array $args
     * @return mixed|string
     */
    private static function format(string $str, $args = null)
    {
        if ($args === null) {
            return $str;
        }
        if (!is_array($args)) {
            $args = [$args];
        }
        if (strlen($str) == 0 || count($args) == 0) {
            return $str;
        }
        return preg_replace_callback('@\{(\d+)\}@', function ($m) use ($args) {
            $index = intval($m[1]);
            return isset($args[$index]) ? $args[$index] : '';
        }, $str);
    }

    public static function __callStatic($name, $args)
    {
        if (preg_match('@^test_(\w+)$@', $name, $match)) {
            $func = self::getFunc($match[1]);
            if ($func !== null) {
                $out = call_user_func_array($func, $args);
                return $out;
            }
        }
        throw new \Exception('Error Method!');
    }

    /**
     * @param $type
     * @return null|string
     */
    public static function getFunc($type)
    {
        $rtype = self::getRealType($type);
        if (method_exists(self::class, 'test_' . $rtype)) {
            return self::class . '::' . 'test_' . $rtype;
        }
        if (isset(self::$staticFunc[$rtype])) {
            return self::$staticFunc[$rtype];
        }
        return null;
    }

    /**
     * @param $type
     * @return mixed
     */
    public static function getRealType($type)
    {
        if (isset(self::$alias[$type])) {
            return self::$alias[$type];
        }
        return $type;
    }

    /**
     * @param $type
     * @param $func
     * @param string $error
     */
    public static function regFnuc($type, $func, $error = '格式错误')
    {
        if (is_string($func)) {
            $func = self::getRealType($func);
            self::$alias[$type] = $func;
            return;
        }
        self::$default_errors[$type] = $error;
        self::$staticFunc[$type] = $func;
    }

    /**
     * 判断是否为空
     * @param string $val
     * @return boolean
     */
    public static function test_required($val)
    {
        if (is_array($val)) {
            return count($val) != 0;
        }
        return $val !== null && $val !== '';
    }

    /**
     * 判断是否是邮箱
     * @param $val
     * @return int
     */
    public static function test_email($val)
    {
        return !!preg_match('/^(\w+[-_\.]?)*\w+@(\w+[-_\.]?)*\w+\.\w{2,6}([\.]\w{2,6})?$/', $val);
    }

    /**
     * 判断url
     * @param $val
     * @param bool $dc
     * @return bool
     */
    public static function test_url($val, $dc = false)
    {
        if ($dc && $val == '#') {
            return true;
        }
        return !!preg_match('/^(http|https|ftp):\/\/\w+\.\w+/i', $val);
    }

    /**
     * 判断值相等
     * @param $val
     * @param $str
     * @return bool
     */
    public static function test_equal($val, $str)
    {
        return strval($val) == strval($str);
    }

    /**
     * 判断值不相等
     * @param $val
     * @param $str
     * @return bool
     */
    public static function test_notequal($val, $str)
    {
        return strval($val) != strval($str);
    }

    /**
     * 判断与比较的id相等
     * @param $val
     * @param $key
     * @return bool
     */
    public static function test_equalto($val, $key)
    {
        if (!empty($key) && preg_match('/^#?(\w+)/i', $key, $m) != 0) {
            $name = isset($m[1]) ? $m[1] : '';
            if (!empty($name)) {
                $str = Request::param($name . ':s');
                if (!empty($str)) {
                    return strval($val) == $str;
                }
            }
        }
        return true;
    }

    /**
     * 判断手机号码
     * @param $val
     * @return bool
     */
    public static function test_mobile($val)
    {
        return !!preg_match('/^1[34578]\d{9}$/', $val);
    }

    /**
     * 判断身份证
     * @param $val
     * @return bool
     */
    public static function test_idcard($val)
    {
        return !!preg_match('/^[1-9]\d{5}(19|20)\d{2}(((0[13578]|1[02])([0-2]\d|30|31))|((0[469]|11)([0-2]\d|30))|(02[0-2][0-9]))\d{3}(\d|X|x)$/', $val);
    }

    /**
     * 判断字母开头的用户名
     * @param $val
     * @return bool
     */
    public static function test_user($val)
    {
        return !!preg_match('/^[a-z]\w*$/i', $val);
    }

    /**
     * 正则校验
     * @param $val
     * @param $re
     * @return bool
     */
    public static function test_regex($val, $re)
    {
        $str = '#' . str_replace('#', '\#', $re) . '#';
        $rt = preg_match($str, $val);
        if ($rt === FALSE) {
            throw new Exception('验证器正则表达式错误!');
        }
        return $rt != 0;
    }

    /**
     * 判断是数字
     * @param $val
     * @return bool
     */
    public static function test_number($val)
    {
        return !!preg_match('/^[\-\+]?((\d+(\.\d*)?)|(\.\d+))$/', $val);
    }

    /**
     * 判断是整数
     * @param $val
     * @return bool
     */
    public static function test_integer($val)
    {
        return !!preg_match('/^[\-\+]?\d+$/', $val);
    }

    /**
     * 判断值
     * @param $val
     * @param $num
     * @param bool $noeq
     * @return bool
     */
    public static function test_max($val, $num, $noeq = false)
    {
        if ($noeq) {
            return floatval($val) < floatval($num);
        } else {
            return floatval($val) <= floatval($num);
        }
    }

    /**
     * 判断值
     * @param $val
     * @param $num
     * @param bool $noeq
     * @return bool
     */
    public static function test_min($val, $num, $noeq = false)
    {
        if ($noeq) {
            return floatval($val) > floatval($num);
        } else {
            return floatval($val) >= floatval($num);
        }
    }

    /**
     * 判断值范围
     * @param $val
     * @param $min
     * @param $max
     * @param bool $noeq
     * @return bool
     */
    public static function test_range($val, $min, $max, $noeq = false)
    {
        return self::test_min($val, $min, $noeq) && self::test_max($val, $max, $noeq);
    }

    /**
     * 字符长度判断
     * @param $val
     * @param $len
     * @return bool
     */
    public static function test_minlength($val, $len)
    {
        return mb_strlen($val, 'UTF-8') >= intval($len);
    }

    /**
     * 字符长度判断
     * @param $val
     * @param $len
     * @return bool
     */
    public static function test_maxlength($val, $len)
    {
        return mb_strlen($val, 'UTF-8') <= intval($len);
    }

    /**
     * 字符区间判断
     * @param $val
     * @param $minlen
     * @param $maxlen
     * @return bool
     */
    public static function test_rangelength($val, $minlen, $maxlen)
    {
        return self::test_minlength($val, $minlen) && self::test_maxlength($val, $maxlen);
    }

    /**
     * 判断是两位小数的金额
     * @param $val
     * @return bool
     */
    public static function test_money($val)
    {
        return preg_match('/^[\-\+]{0,1}\d+[\.]\d{1,2}$/', $val) != 0 || self::test_integer($val);
    }

    /**
     * 判断日期时间格式
     * @param $val
     * @return bool
     */
    public static function test_date($val)
    {
        return !!preg_match('/^\d{4}-\d{1,2}-\d{1,2}(\s\d{1,2}(:\d{1,2}(:\d{1,2})?)?)?$/', $val);
    }

    /**
     * 添加远程校验
     * @param string $name
     * @param $func
     */
    public function addRemoute(string $name, $func)
    {
        $this->remoteFunc[$name] = $func;
    }

    /**
     * 添加校验函数
     * @param string $type
     * @param $func
     * @param string|null $error
     */
    public function addFunc(string $type, $func, string $error = null)
    {
        $this->func[$type] = $func;
        if (!empty($error)) {
            $this->def_errors[$type] = $error;
        }
    }

    /**
     * 字段检查
     * @param Field $field
     * @return bool
     */
    public function checkField(Field $field)
    {
        if (Validate::$default_errors == null) {
            Validate::$default_errors = Config::get('form.validate_default_errors', []);
        }
        if (!empty($field->error)) {
            return false;
        }
        if ($field->dataValDisabled) {
            return true;
        }
        if (!empty($field->childError)) {
            return false;
        }
        $value = $field->value;
        $validFunc = $field->getFunc('valid');
        if ($validFunc && is_callable($validFunc)) {
            $error = $validFunc($value);
            if (!empty($error)) {
                $field->error = $error;
                return false;
            }
        }
        $rules = $field->dataValRule;
        if ($rules == null) {
            return true;
        }
        $errors = $field->dataValMessage;
        if ($errors == null) {
            $errors = [];
        }
        $tempErrors = [];
        foreach ($errors as $type => $err) {
            $realType = Validate::getRealType($type);
            $tempErrors[$realType] = $err;
        }
        $errors = $tempErrors;
        $tempRules = [];
        foreach ($rules as $type => $args) {
            $realType = Validate::getRealType($type);
            $tempRules[$realType] = $args;
        }
        $rules = $tempRules;
        //验证非空
        if (isset($rules['required']) && $rules['required']) {
            $func = isset($this->func['required']) ? $this->func['required'] : Validate::getFunc('required');
            $r = call_user_func_array($func, [$value]);
            if (!$r) {
                $err = isset($this->def_errors['required']) ? $this->def_errors['required'] : (isset($errors['required']) ? $errors['required'] : (isset(Validate::$default_errors['required']) ? Validate::$default_errors['required'] : '必填项'));
                $field->error = $err;
                return false;
            }
            unset($rules['required']);
        }
        if (is_array($value)) {
            return true;
        }
        if (strlen($value) > 0 || (isset($rules['force']) && $rules['force'])) {
            unset($rules['force']);
            foreach ($rules as $type => $args) {
                if (!is_array($args)) {
                    $args = [$args];
                }
                $param = array_slice($args, 0);
                array_unshift($args, $value);
                $func = isset($this->func[$type]) ? $this->func[$type] : Validate::getFunc($type);
                if ($func == null) {
                    continue;
                }
                $out = call_user_func_array($func, $args);
                if (is_bool($out)) {
                    if ($out) {
                        continue;
                    }
                    $err = isset($this->def_errors[$type]) ? $this->def_errors[$type] : (isset($errors[$type]) ? $errors[$type] : (isset(Validate::$default_errors[$type]) ? Validate::$default_errors[$type] : '格式错误'));
                    $field->error = Validate::format($err, $param);
                    return false;
                }
                if (is_array($out) && isset($out['status']) && !$out['status'] && !empty($out['error'])) {
                    $field->error = Validate::format($out['error'], $param);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 验证数据返回错误集合
     * @param array $rule
     */
    public static function validRules(array $input = [], array $ruleMap = [], array &$errors = [], bool $suspend = false)
    {
        if (Validate::$default_errors == null) {
            Validate::$default_errors = Config::get('form.validate_default_errors', []);
        }
        $tempErrors = [];
        $result = true;
        foreach ($ruleMap as $key => $item) {
            $tempErrors[$key] = '';
            if (!isset($item['rule'])) {
                $item['rule'] = null;
            }
            if (!isset($item['message'])) {
                $item['message'] = null;
            }
            if (isset($input[$key])) {
                $result = self::checkValue($input[$key], $item['rule'], $item['message'], $tempErrors[$key]) && $result;
            } else {
                $result = self::checkValue(null, $item['rule'], $item['message'], $tempErrors[$key]) && $result;
            }
            if (!$result) {
                $errors[$key] = $tempErrors[$key];
            }
            if ($suspend && !$result) {
                return false;
            }
        }
        return $result;
    }

    /**
     * 验证返回当个错误
     * @param array $input
     * @param array $ruleMap
     * @param string $errors
     * @return bool
     */
    public static function validRule(array $input = [], array $ruleMap = [], string &$errors = '')
    {
        $tempErrors = [];
        $result = self::validRules($input, $ruleMap, $tempErrors, true);
        if (!$result) {
            $errors = current($tempErrors);
            return false;
        }
        return true;
    }

    /**
     * 验证单个错误
     * @param array $value
     * @param array $rule
     */
    public static function checkValue($value, array $rules = null, array $message = null, string &$error)
    {
        $validFunc = isset($rule['validFunc']);
        if ($validFunc && is_callable($validFunc)) {
            $err = $validFunc($value);
            if (!empty($err)) {
                $error = $err;
                return false;
            }
        }
        if ($rules == null || count($rules) == 0) {
            return true;
        }
        if ($message == null) {
            $message = [];
        }
        //获取
        $tempMegs = [];
        foreach ($message as $type => $msg) {
            $realType = Validate::getRealType($type);
            $tempMegs[$realType] = $msg;
        }
        $message = $tempMegs;
        $tempRules = [];
        foreach ($rules as $type => $args) {
            $realType = Validate::getRealType($type);
            $tempRules[$realType] = $args;
        }
        $rules = $tempRules;
        //验证非空
        if (isset($rules['required']) && $rules['required']) {
            $func = Validate::getFunc('required');
            $r = call_user_func_array($func, [$value]);
            if (!$r) {
                $error = (isset($message['required']) ? $message['required'] : (isset(Validate::$default_errors['required']) ? Validate::$default_errors['required'] : '必填项'));
                return false;
            }
            unset($rules['required']);
        }
        //如果是数组直接放弃
        if (is_array($value) || $value instanceof \stdClass) {
            return true;
        }

        if (strlen($value) > 0 || (isset($rules['force']) && $rules['force'])) {
            unset($rules['force']);
            foreach ($rules as $type => $args) {
                if (!is_array($args)) {
                    $args = [$args];
                }
                $param = array_slice($args, 0);
                array_unshift($args, $value);
                $func = Validate::getFunc($type);
                if ($func == null) {
                    continue;
                }
                $out = call_user_func_array($func, $args);
                if (is_bool($out)) {
                    if ($out) {
                        continue;
                    }
                    $err = (isset($message[$type]) ? $message[$type] : (isset(Validate::$default_errors[$type]) ? Validate::$default_errors[$type] : '格式错误'));
                    $error = Validate::format($err, $param);
                    return false;
                }
                if (is_array($out) && isset($out['status']) && !$out['status'] && !empty($out['error'])) {
                    $error = Validate::format($out['error'], $param);
                    return false;
                }
            }
        }
        return true;
    }
}