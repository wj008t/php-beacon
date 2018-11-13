<?php

namespace beacon;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/11
 * Time: 2:02
 */
class Utils
{
    /**
     * 路径
     * @param mixed ...$paths
     * @return string
     */
    public static function path(...$paths)
    {
        $protocol = '';
        $path = trim(implode(DIRECTORY_SEPARATOR, $paths));
        if (preg_match('@^([a-z0-9]+://|/)(.*)@i', $path, $m)) {
            $protocol = $m[1];
            $path = $m[2];
        }
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
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
     * 驼峰转下划线
     * @param $name
     * @return null|string|string[]
     */
    public static function toUnder($name)
    {
        $name = preg_replace_callback('@[A-Z]@', function ($m) {
            return '_' . strtolower($m[0]);
        }, $name);
        $name = ltrim($name, '_');
        return $name;
    }

    /**
     * 下划线转驼峰
     * @param $name
     * @return null|string|string[]
     */
    public static function toCamel($name)
    {
        $name = preg_replace('@_+@', '_', $name);
        $name = preg_replace_callback('@_[a-z]@', function ($m) {
            return substr(strtoupper($m[0]), 1);
        }, $name);
        $name = ucfirst($name);
        return $name;
    }

    /**
     * 属性 转 驼峰
     * @param $name
     * @return null|string|string[]
     */
    public static function attrToCamel($name)
    {
        $name = preg_replace_callback('@-[a-z]@', function ($m) {
            return substr(strtoupper($m[0]), 1);
        }, trim($name, '-'));
        $name = lcfirst($name);
        return $name;
    }

    /**
     * 驼峰转属性
     * @param $name
     * @return null|string|string[]
     */
    public static function camelToAttr($name)
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
    public static function randWord($len = 4)
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
    public static function randNum($len = 4)
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
    public static function isJson($str)
    {
        return is_string($str) && !empty($str) && preg_match('@^[\[\{].*[\]\}]$@', $str);
    }

    /**
     * 创建文件夹
     * @param $dir
     * @param int $mode
     */
    public static function makeDir($dir, $mode = 0777)
    {
        if (!is_dir($dir)) {
            $pDir = dirname($dir);
            self::makeDir($pDir);
            @mkdir($dir, $mode);
        }
    }

    /**
     * 扩展数组
     * @param array $a1
     * @param array $a2
     * @return array
     */
    public static function extend(array $a1, array $a2)
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

}
