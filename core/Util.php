<?php


namespace beacon\core;


class Util
{
    /**
     * 路径
     * @param mixed ...$paths
     * @return string
     */
    public static function path(string ...$paths): string
    {
        $protocol = '';
        $path = trim(implode(DIRECTORY_SEPARATOR, $paths));
        if (preg_match('@^([a-z0-9]+://|/)(.*)@i', $path, $m)) {
            $protocol = $m[1];
            $path = $m[2];
        }
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        $path = implode(DIRECTORY_SEPARATOR, $absolutes);
        if (DIRECTORY_SEPARATOR == '\\' && isset($protocol[4])) {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }
        return $protocol . $path;
    }

    /**
     * 修剪掉root路径
     * @param $path
     * @param string $base
     * @return string
     */
    public static function trimPath($path, $base = ROOT_DIR): string
    {
        $len = strlen($base);
        if (strlen($path) > $len) {
            if (substr($path, 0, $len) == $base) {
                $path = substr($path, $len);
            }
        }
        return $path;
    }

    /**
     * 驼峰转下划线
     * @param string $name
     * @return string
     */
    public static function toUnder(string $name): string
    {
        $name = preg_replace_callback('@[A-Z]@', function ($m) {
            return '_' . strtolower($m[0]);
        }, $name);
        $name = ltrim($name, '_');
        return $name;
    }

    /**
     * 下划线转驼峰
     * @param string $name
     * @param bool $lc
     * @return string
     */
    public static function toCamel(string $name, bool $lc = false): string
    {
        $name = preg_replace('@[_-]+@', '_', $name);
        $name = preg_replace_callback('@_[a-z]@', function ($m) {
            return substr(strtoupper($m[0]), 1);
        }, $name);
        if ($lc) {
            $name = lcfirst($name);
        } else {
            $name = ucfirst($name);
        }
        return $name;
    }

    /**
     * 属性 转 驼峰
     * @param string $name
     * @return string
     */
    public static function attrToCamel(string $name): string
    {
        $name = preg_replace_callback('@-[a-z]@', function ($m) {
            return substr(strtoupper($m[0]), 1);
        }, trim($name, '-'));
        $name = lcfirst($name);
        return $name;
    }

    /**
     * 驼峰转属性
     * @param string $name
     * @return string
     */
    public static function camelToAttr(string $name): string
    {
        $name = preg_replace_callback('@[A-Z]@', function ($m) {
            return '-' . strtolower($m[0]);
        }, $name);
        $name = ltrim($name, '-');
        return $name;
    }

    /**
     * 随机字母数字
     * @param int $len
     * @return string
     */
    public static function randWord(int $len = 4): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $word = '';
        for ($i = 0; $i < $len; $i++) {
            $word .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $word;
    }

    /**
     * 随机数字
     * @param int $len
     * @return string
     */
    public static function randNum(int $len = 4): string
    {
        $chars = '0123456789';
        $word = '';
        for ($i = 0; $i < $len; $i++) {
            $word .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $word;
    }

    /**
     * 是否json格式
     * @param $str
     * @return bool
     */
    public static function isJson(string $str): bool
    {
        return is_string($str) && !empty($str) && preg_match('@^[[{].*[]}]$@', $str);
    }

    /**
     * 创建文件夹
     * @param string $dir
     * @param int $mode
     */
    public static function makeDir(string $dir, int $mode = 0777)
    {
        if (!is_dir($dir)) {
            try {
                $pDir = dirname($dir);
                self::makeDir($pDir);
                @mkdir($dir, $mode, true);
            } catch (\Exception $e) {
                Logger::log($e->getMessage(), $e->getTraceAsString());
            }
        }
    }

    /**
     * 扩展数组
     * @param array $a1
     * @param array $a2
     * @return array
     */
    public static function extend(array $a1, array $a2): array
    {
        foreach ($a2 as $key => $item) {
            if (!isset($a1[$key])) {
                $a1[$key] = $item;
            } else {
                if (is_array($item) && is_array($a1[$key])) {
                    $a1[$key] = self::extend($a1[$key], $item);
                } else {
                    $a1[$key] = $item;
                }
            }
        }
        return $a1;
    }

    /**
     * 类型转换
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    public static function convertType(mixed $value, string $type): mixed
    {
        $temp = explode('|', $type);
        $supNull = false;
        if (in_array('null', $temp) || $temp[0][0] == '?') {
            $supNull = true;
        }
        $type = ltrim($temp[0], '?');
        switch ($type) {
            case 'bool':
            case 'boolean':
                if ($value === null && !$supNull) {
                    return false;
                }
                if (is_string($value)) {
                    return $value === '1' || $value === 'true';
                }
                return !!$value;
            case 'int':
                if ($value === null && !$supNull) {
                    return 0;
                }
                return intval($value);
            case 'double':
            case 'float':
                if ($value === null && !$supNull) {
                    return 0;
                }
                return floatval($value);
            case 'string':
                if ($value === null && !$supNull) {
                    return '';
                }
                return strval($value);
            case 'array':
                if ($value === null && !$supNull) {
                    return [];
                }
                if (is_array($value)) {
                    return $value;
                }
                if (is_string($value) && Util::isJson($value)) {
                    return json_decode($value, true);
                }
                return [$value];
            default :
                return $value;
        }
    }

    /**
     * 转换数组元素
     * @param array $values
     * @param string $type
     * @return array
     */
    public static function mapItemType(array $values, string $type): array
    {
        return array_map(function ($v) use ($type) {
            switch ($type) {
                case 'int':
                    return intval($v);
                case 'float':
                case 'double':
                    return floatval($v);
                case 'bool':
                case 'boolean':
                    return $v === true || $v === 1 || strval($v) == '1' || strval($v) == 'true';
                case 'object':
                case 'array':
                    return $v;
                default:
                    return strval($v);
            }
        }, $values);
    }

    /**
     * 是否有类型
     * @param string $haystack
     * @return array
     */
    public static function typeMap(string $haystack): array
    {
        if (empty($haystack)) {
            return [];
        }
        static $cache = [];
        if (isset($cache[$haystack])) {
            return $cache[$haystack];
        }
        $map = [];
        if ($haystack[0] == '?') {
            $typ = ltrim($haystack, '?');
            $map[$typ] = $typ;
            $map['null'] = 'null';
        } else {
            $temp = explode('|', $haystack);
            $hasNull = false;
            foreach ($temp as $typ) {
                if ($typ == 'null') {
                    $hasNull = true;
                    continue;
                }
                $map[$typ] = $typ;
            }
            if ($hasNull) {
                $map['null'] = 'null';
            }
        }
        $cache[$haystack] = $map;
        return $map;
    }

}