<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-11-14
 * Time: 下午12:39
 */

namespace beacon;

/**
 * 日志管理类
 * Class Logger
 * @package beacon
 */
class Logger
{
    private static $_logger = null;

    /**
     * @return null
     */
    public static function logger()
    {
        if (self::$_logger) {
            return self::$_logger;
        }
        $className = Config::get('beacon.log_class_name');
        if (!empty($className) && class_exists($className)) {
            self::$_logger = new $className();
            return self::$_logger;
        }
        return null;
    }

    public static function log(...$args)
    {
        $logger = self::logger();
        if ($logger) {
            $logger->log(...$args);
        }
    }

    public static function error(...$args)
    {
        $logger = self::logger();
        if ($logger) {
            $logger->error(...$args);
        }
    }

    public static function warn(...$args)
    {
        $logger = self::logger();
        if ($logger) {
            $logger->warn(...$args);
        }
    }

    public static function info(...$args)
    {
        $logger = self::logger();
        if ($logger) {
            $logger->info(...$args);
        }
    }
}