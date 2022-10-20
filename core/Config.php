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
    public static array $global = []; //全局的
    private static array $loaded = []; //已经加载过的

    /**
     * 从配置文件目录中加载
     * @param $name
     * @param bool $overwrite
     * @return array
     */
    public static function load(string|array $name, bool $overwrite = false): array
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
        self::$loaded[$name] = 1;
        $filePath = Util::path(self::$configPath, $name . '.config.php');
        if (file_exists($filePath)) {
            $loadData = require($filePath);
            if (is_array($loadData)) {
                foreach ($loadData as $key => $val) {
                    $nKey = $name . '.' . $key;
                    if ($overwrite || !isset(self::$global[$nKey])) {
                        self::$global[$nKey] = $val;
                    }
                }
            }
            return $loadData;
        }
        return [];
    }

    /**
     * 指定绝对路径加载
     * @param string $file
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
    public static function set(string $key, $value): void
    {
        self::$global[$key] = $value;
    }

    /**
     * 追加配置
     * @param array $value
     */
    public static function append(array $value): void
    {
        foreach ($value as $key => $val) {
            self::$global[$key] = $val;
        }
    }

    /**
     * 获取配置项
     * @param string|null $key
     * @param null $default
     * @return mixed
     */
    public static function get(string $key = null, mixed $default = null): mixed
    {
        if ($key == null) {
            return self::$global;
        }
        if (isset(self::$global[$key])) {
            return self::$global[$key];
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
        return self::$global[$key] ?? $default;
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
    public static function setSection(string $name, array $data): void
    {
        foreach ($data as $key => $val) {
            self::$global[$name . '.' . $key] = $val;
        }
    }
}
