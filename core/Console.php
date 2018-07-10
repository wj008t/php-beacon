<?php

namespace beacon;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/28
 * Time: 12:14
 */

class Console
{


    private static function _send($type, $args)
    {

        if (!(defined('DEBUG_LOG') && DEBUG_LOG)) {
            return;
        }
        if (!get_extension_funcs('sockets')) {
            return;
        }
        $backtrace = debug_backtrace(false);
        $backtrace_message = 'unknown';
        if (isset($backtrace[1]) && isset($backtrace[1]['file']) && isset($backtrace[1]['line'])) {
            $backtrace_message = $backtrace[1]['file'] . ' : ' . $backtrace[1]['line'];
        }
        foreach ($args as &$arg) {
            $arg = self::_convert($arg);
        }
        $data = [];
        $data[0] = $args;
        $data[1] = $backtrace_message;
        $data[2] = $type;
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $msg = json_encode($data);
        $len = strlen($msg);
        try {
            socket_sendto($sock, $msg, $len, 0, Config::get('debug.udp_addr', '127.0.0.1'), Config::get('debug.udp_port', 1024));
            socket_close($sock);
        } catch (\Exception $e) {

        } catch (\ErrorException $e) {

        }

    }

    public static function addSql($sql, $time = 0)
    {
        if (Config::get('debug.show_sql', true)) {
            self::_send('sql', [$sql, intval($time * 100000) / 100 . 'ms']);
        }
    }

    public static function log(...$args)
    {
        self::_send('log', $args);
    }

    public static function warn(...$args)
    {
        self::_send('warn', $args);
    }

    public static function error(...$args)
    {
        self::_send('error', $args);
    }

    public static function group(...$args)
    {
        self::_send('group', $args);
    }

    public static function info(...$args)
    {
        self::_send('info', $args);
    }

    public static function groupEnd(...$args)
    {
        self::_send('groupEnd', $args);
    }

    public static function groupCollapsed(...$args)
    {
        self::_send('groupCollapsed', $args);
    }

    public static function table(...$args)
    {
        self::_send('table', $args);
    }

    private static function _getPropertyKey(\ReflectionProperty $property)
    {
        $static = $property->isStatic() ? ' static' : '';
        if ($property->isPublic()) {
            return 'public' . $static . ' ' . $property->getName();
        }
        if ($property->isProtected()) {
            return 'protected' . $static . ' ' . $property->getName();
        }
        if ($property->isPrivate()) {
            return 'private' . $static . ' ' . $property->getName();
        }
    }

    private static function _convert($object)
    {
        if (!is_object($object)) {
            return $object;
        }
        static $_processed = [];
        $_processed[] = $object;
        $object_as_array = [];
        $object_as_array['___class_name'] = get_class($object);
        $object_vars = get_object_vars($object);
        //解析属性值
        foreach ($object_vars as $key => $value) {
            if ($value === $object || in_array($value, $_processed, true)) {
                $value = 'recursion - parent object [' . get_class($value) . ']';
            }
            $object_as_array[$key] = self::_convert($value);
        }
        $reflection = new \ReflectionClass($object);
        foreach ($reflection->getProperties() as $property) {
            if (array_key_exists($property->getName(), $object_vars)) {
                continue;
            }
            $type = static::_getPropertyKey($property);
            if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
                $property->setAccessible(true);
            }
            try {
                $value = $property->getValue($object);
            } catch (\ReflectionException $e) {
                $value = 'only PHP 5.3 can access private/protected properties';
            }
            // same instance as parent object
            if ($value === $object || in_array($value, $_processed, true)) {
                $value = 'recursion - parent object [' . get_class($value) . ']';
            }
            $object_as_array[$type] = self::_convert($value);
        }
        return $object_as_array;
    }
}
