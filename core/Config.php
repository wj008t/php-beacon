<?php

namespace beacon\core;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/12
 * Time: 15:56
 */
class Config
{

    private static string|null $configPath = null;
    private static array $global = []; //全局的
    private static array $loaded = []; //已经加载过的

    /**
     * 从配置文件目录中加载
     * @param $name
     * @param bool $overwrite
     * @return array
     */
    public static function load(string $name, bool $overwrite = false): array
    {
        if (is_array($name)) {
            $data = [];
            foreach ($name as $value) {
                $temp = self::load($value);
                $data = array_merge($data, $temp);
            }
            return $data;
        }
        //使用根目录下的配置文件
        if (empty(self::$configPath)) {
            self::$configPath = Util::path(ROOT_DIR, 'config');
        }
        $filePath = Util::path(self::$configPath, $name . '.config.php');
        if (file_exists($filePath)) {
            $loadData = require($filePath);
            if (is_array($loadData)) {
                foreach ($loadData as $key => $val) {
                    if ($overwrite) {
                        self::$global[$name . '.' . $key] = $val;
                    } else {
                        if (!isset(self::$global[$name . '.' . $key])) {
                            self::$global[$name . '.' . $key] = $val;
                        }
                    }
                }
            }
            self::$loaded[$name] = 1;
            return $loadData;
        }
        return [];
    }

    /**
     * 指定绝对路径加载
     * @param $file
     * @return array
     */
    public static function loadFile(string $file): array
    {
        $path = Util::path($file);
        if (is_file($path)) {
            return require($path);
        }
        return [];
    }

    /**
     * 设置配置项
     * @param string $key
     * @param $value
     */
    public static function set(string $key, $value)
    {
        self::$global[$key] = $value;
    }

    /**
     * 追加配置
     * @param array $value
     */
    public static function append(array $value)
    {
        foreach ($value as $key => $val) {
            self::$global[$key] = $val;
        }
    }

    /**
     * 获取配置项
     * @param string|null $key
     * @param null $default
     * @return array|string|null|int|callable|float|bool
     */
    public static function get(string $key = null, $default = null): array|string|null|int|callable|float|bool
    {
        if ($key == null) {
            return self::$global;
        }
        if (preg_match('@^(\w+)\.(.+)$@', $key, $m)) {
            $name = trim($m[1]);
            if (!isset(self::$loaded[$name])) {
                self::load($name);
            }
            if (trim($m[2]) == '*') {
                return self::getSection($name);
            }
        }
        return isset(self::$global[$key]) ? self::$global[$key] : $default;
    }

    /**
     * 获取一栏配置项
     * @param string $name
     * @return array
     */
    public static function getSection(string $name): array
    {
        $section = [];
        if (!isset(self::$loaded[$name])) {
            self::load($name);
        }
        foreach (self::$global as $key => $val) {
            if (preg_match('@^' . preg_quote($name, '@') . '\.(.+)$@', $key, $m)) {
                $section[$m[1]] = $val;
            }
        }
        return $section;
    }

    /**
     * 设置一栏配置项
     * @param string $name
     * @param array $data
     */
    public static function setSection(string $name, array $data)
    {
        foreach ($data as $key => $val) {
            self::$global[$name . '.' . $key] = $val;
        }
    }
}
