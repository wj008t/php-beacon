<?php

namespace beacon\core;


class Validator
{
    /**
     * 默认消息
     * @var array|null
     */
    protected static array|null $default_errors = null;
    /**
     * 代理短写
     * @var array<string,string>
     */
    protected static array $alias = [
        'r' => 'required',
        'i' => 'integer',
        'int' => 'integer',
        'num' => 'number',
        'minLen' => 'minLength',
        'maxLen' => 'maxLength',
        'rangeLen' => 'rangeLength',
        'eqTo' => 'equalTo',
        'eq' => 'equal',
        'neq' => 'notEqual'
    ];
    /**
     * 静态方法
     * @var callable[]
     */
    protected static array $staticFunc = [];

    /**
     * @var callable[]
     */
    protected static array $remoteFunc = [];

    /**
     * 静态调用初始化
     */
    public static function init(): void
    {
        if (Validator::$default_errors == null) {
            Validator::$default_errors = Config::get('form.validate_default_errors', []);
        }
    }

    /**
     * 字符串格式化输出
     * @param string $str
     * @param array $args
     * @return string
     */
    protected static function format(string $str, mixed $args = null): string
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
        return preg_replace_callback('@{(\d+)}@', function ($m) use ($args) {
            $index = intval($m[1]);
            return $args[$index] ?? '';
        }, $str);
    }

    /**
     * 静态使用
     * @param string $name
     * @param array $args
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic(string $name, array $args): mixed
    {
        if (preg_match('@^test([A-Z]\w*)$@', $name, $match)) {
            $func = self::getFunc(lcfirst($match[1]));
            if ($func !== null) {
                return call_user_func_array($func, $args);
            }
        }
        throw new \Exception('Error Method!');
    }

    /**
     * 获取执行方法
     * @param $type
     * @return string|callable|null
     */
    public static function getFunc($type): string|callable|null
    {
        $rType = self::getRealType($type);
        $method = 'test' . ucfirst($type);
        if (method_exists(self::class, $method)) {
            return self::class . '::' . $method;
        }
        if (isset(self::$staticFunc[$rType])) {
            return self::$staticFunc[$rType];
        }
        return null;
    }

    /**
     * 获取真实类型
     * @param string $type
     * @return string
     */
    public static function getRealType(string $type): string
    {
        if (isset(self::$alias[$type])) {
            return self::$alias[$type];
        }
        return $type;
    }

    /**
     * 注册函数
     * @param string $type
     * @param string|callable $func
     * @param string $error
     */
    public static function regFunc(string $type, string|callable $func, string $error = '格式错误')
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
     * @param mixed $val
     * @return bool
     */
    public static function testRequired(mixed $val): bool
    {
        if (is_array($val)) {
            return count($val) != 0;
        }
        return $val !== null && $val !== '';
    }

    /**
     * 判断是否是邮箱
     * @param string $val
     * @return bool
     */
    public static function testEmail(string $val): bool
    {
        return !!preg_match('/^(\w+[-_.]?)*\w+@(\w+[-_.]?)*\w+\.\w{2,6}([.]\w{2,6})?$/', $val);
    }

    /**
     * 判断url
     * @param $val
     * @param bool $dc
     * @return bool
     */
    public static function testUrl(string $val, bool $dc = false): bool
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
    public static function testEqual(mixed $val, mixed $str): bool
    {
        return strval($val) == strval($str);
    }

    /**
     * 判断值不相等
     * @param $val
     * @param $str
     * @return bool
     */
    public static function testNotEqual(mixed $val, mixed $str): bool
    {
        return strval($val) != strval($str);
    }

    /**
     * 判断与比较的id相等
     * @param $val
     * @param $key
     * @return bool
     */
    public static function testEqualTo(mixed $val, string $key): bool
    {
        if (!empty($key) && preg_match('/^#?(\w+)/i', $key, $m) != 0) {
            $name = $m[1] ?? '';
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
    public static function testMobile(string $val): bool
    {
        return !!preg_match('/^1[3456789]\d{9}$/', $val);
    }

    /**
     * 判断身份证
     * @param string $val
     * @return bool
     */
    public static function testIdCard(string $val): bool
    {
        return !!preg_match('/^[1-9]\d{5}(19|20)\d{2}(((0[13578]|1[02])([0-2]\d|30|31))|((0[469]|11)([0-2]\d|30))|(02[0-2][0-9]))\d{3}(\d|X|x)$/', $val);
    }

    /**
     * 判断字母开头的用户名
     * @param $val
     * @return bool
     */
    public static function testUser($val): bool
    {
        return !!preg_match('/^[a-z]\w*$/i', $val);
    }

    /**
     * 正则校验
     * @param string $val
     * @param string $re
     * @return bool
     * @throws \Exception
     */
    public static function testRegex(string $val, string $re): bool
    {
        $str = '#' . str_replace('#', '\#', $re) . '#';
        $rt = preg_match($str, $val);
        if ($rt === false) {
            throw new \Exception('验证器正则表达式错误!');
        }
        return $rt != 0;
    }

    /**
     * 判断是数字
     * @param mixed $val
     * @return bool
     */
    public static function testNumber(mixed $val): bool
    {
        return !!preg_match('/^[-+]?((\d+(\.\d*)?)|(\.\d+))$/', strval($val));
    }

    /**
     * 判断是整数
     * @param $val
     * @return bool
     */
    public static function testInteger(mixed $val): bool
    {
        return !!preg_match('/^[-+]?\d+$/', strval($val));
    }

    /**
     * 判断值
     * @param $val
     * @param $num
     * @param bool $nq
     * @return bool
     */
    public static function testMax(mixed $val, mixed $num, bool $nq = false): bool
    {
        if (!is_int($val)) {
            $val = round(floatval($val), 5);
        }
        if (!is_int($num)) {
            $num = round(floatval($num), 5);
        }
        if ($nq) {
            return $val < $num;
        } else {
            return $val <= $num;
        }
    }

    /**
     * 判断值
     * @param $val
     * @param $num
     * @param bool $nq
     * @return bool
     */
    public static function testMin(mixed $val, mixed $num, bool $nq = false): bool
    {
        if (!is_int($val)) {
            $val = round(floatval($val), 5);
        }
        if (!is_int($num)) {
            $num = round(floatval($num), 5);
        }
        if ($nq) {
            return $val > $num;
        } else {
            return $val >= $num;
        }
    }

    /**
     * 判断值范围
     * @param $val
     * @param $min
     * @param $max
     * @param bool $nq
     * @return bool
     */
    public static function testRange(mixed $val, $min, mixed $max, bool $nq = false): bool
    {
        return self::testMin($val, $min, $nq) && self::testMax($val, $max, $nq);
    }

    /**
     * 字符长度判断
     * @param $val
     * @param $len
     * @return bool
     */
    public static function testMinLength(string $val, int|string $len): bool
    {
        return mb_strlen($val, 'UTF-8') >= intval($len);
    }

    /**
     * 字符长度判断
     * @param $val
     * @param $len
     * @return bool
     */
    public static function testMaxLength(string $val, int|string $len): bool
    {
        return mb_strlen($val, 'UTF-8') <= intval($len);
    }

    /**
     * 字符区间判断
     * @param $val
     * @param $min
     * @param $max
     * @return bool
     */
    public static function testRangeLength(string $val, int|string $min, int|string $max): bool
    {
        return self::testMinLength($val, $min) && self::testMaxLength($val, $max);
    }

    /**
     * 判断是两位小数的金额
     * @param $val
     * @return bool
     */
    public static function testMoney(mixed $val): bool
    {
        return preg_match('/^[-+]?\d+[.]\d{1,2}$/', strval($val)) != 0 || self::testInteger($val);
    }

    /**
     * 判断日期时间格式
     * @param $val
     * @return bool
     */
    public static function testDate($val): bool
    {
        return !!preg_match('/^\d{4}-\d{1,2}-\d{1,2}(\s\d{1,2}(:\d{1,2}(:\d{1,2})?)?)?$/', $val);
    }

    /**
     * 添加远程校验
     * @param string $name
     * @param callable $func
     */
    public static function addRemote(string $name, callable $func)
    {
        static::$remoteFunc[$name] = $func;
    }

    /**
     * @param mixed $value
     * @param array|null $valid
     * @param ?string $error
     * @return bool
     */
    public static function checkValue(mixed $value, ?array $valid = null, ?string &$error = ''): bool
    {
        if ($valid == null || count($valid) == 0) {
            return true;
        }
        $validFunc = $valid['func'] ?? null;
        if ($validFunc && is_callable($validFunc)) {
            list($ret, $err) = $validFunc($value);
            if (!$ret) {
                if (!empty($err)) {
                    $error = $err;
                }
                return false;
            }
        }
        $disabled = isset($valid['disabled']) && boolval($valid['disabled']);
        if ($disabled) {
            return true;
        }
        $rule = $valid['rule'] ?? null;
        if (empty($rule)) {
            return true;
        }
        $maps = [];
        foreach ($rule as $type => $args) {
            $realType = Validator::getRealType($type);
            if (is_array($args)) {
                array_unshift($args, $value);
                $message = array_pop($args);
                $maps[$realType] = ['args' => $args, 'message' => $message];
            } else if (is_string($args)) {
                $maps[$realType] = ['args' => [$value], 'message' => $args];
            }
        }
        //验证非空
        if (isset($maps['required']) && $maps['required']) {
            $item = $maps['required'];
            $func = Validator::getFunc('required');
            $r = call_user_func_array($func, $item['args']);
            if (!$r) {
                $error = empty($item['message']) ? (Validator::$default_errors['required'] ?? '必填项') : $item['message'];
                return false;
            }
            unset($maps['required']);
        }
        //如果是数组直接放弃
        if (is_array($value) || is_object($value)) {
            return true;
        }
        //检查格式
        if (strlen($value) > 0 || (isset($maps['force']) && $maps['force'])) {
            unset($maps['force']);
            foreach ($maps as $type => $item) {
                $param = array_slice($item['args'], 1);
                $func = Validator::getFunc($type);
                if ($func == null) {
                    continue;
                }
                $out = call_user_func_array($func, $item['args']);
                if (is_bool($out)) {
                    if ($out) {
                        continue;
                    }
                    $err = empty($item['message']) ? (Validator::$default_errors[$type] ?? '格式错误') : $item['message'];
                    $error = Validator::format($err, $param);
                    return false;
                } else if (is_array($out) && isset($out['status']) && !$out['status'] && !empty($out['error'])) {
                    $error = Validator::format($out['error'], $param);
                    return false;
                }
            }
        }
        return true;
    }
}

Validator::init();

