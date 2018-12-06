<?php

namespace beacon;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/12
 * Time: 15:56
 */
class Config
{

    private static $configPath = null;
    private static $global = []; //全局的
    private static $loaded = []; //已经加载过的

    /**
     * 从配置文件目录中加载
     * @param $name
     * @param bool $overwrite
     * @return mixed|null
     */
    public static function load($name, $overwrite = false)
    {
        if (is_array($name)) {
            $data = [];
            foreach ($name as $value) {
                $temp = self::load($value);
                $data = array_merge($data, $temp);
            }
            return $data;
        }

        //如果应用目录下存在配置文件夹使用应用目录下面的配置文件
        $appPath = Route::getPath();
        if (!empty($appPath)) {
            $filePath = Utils::path($appPath, 'config', $name . '.config.php');
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
        }
        //使用根目录下的配置文件
        if (empty(self::$configPath)) {
            self::$configPath = Utils::path(ROOT_DIR, 'config');
        }
        $filePath = Utils::path(self::$configPath, $name . '.config.php');
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
     * @return mixed|null
     */
    public static function loadFile(string $file)
    {
        $path = Utils::path($file);
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
     * 获取配置项
     * @param string $key
     * @param string $def 默认值
     * @return mixed|string
     */
    public static function get(string $key = null, $def = null)
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
        return isset(self::$global[$key]) ? self::$global[$key] : $def;
    }

    /**
     * 获取一栏配置项
     * @param string $name
     * @return array
     */
    public static function getSection(string $name)
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
