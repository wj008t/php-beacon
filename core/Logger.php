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

    private static $log_udp_ip = "";
    private static $log_udp_port = 0;

    private static $sock = null;

    /**
     * UDP发送
     * @param string $type
     * @param array $args
     * @param null $time
     */
    private static function send(string $type, array $args, $time = null)
    {
        if (!(defined('DEBUG_LOG') && DEBUG_LOG)) {
            return;
        }
        if (!get_extension_funcs('sockets')) {
            return;
        }
        if (empty(self::$log_udp_ip) || empty(self::$log_udp_port)) {
            self::$log_udp_ip = Config::get('beacon.log_udp_addr', '127.0.0.1');
            self::$log_udp_port = Config::get('beacon.log_udp_port', 1024);
        }
        $backtrace = debug_backtrace(false);
        $backtrace_message = 'unknown';
        if (isset($backtrace[1]) && isset($backtrace[1]['file']) && isset($backtrace[1]['line'])) {
            $backtrace_message = $backtrace[1]['file'] . '(' . $backtrace[1]['line'] . ')';
        }
        $temps = [];
        foreach ($args as $arg) {
            try {
                $arg = self::convert($arg);
            } catch (\ReflectionException $exception) {
            }
            $temp = json_encode($arg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($temp[0] != '{' && $temp[0] != '[') {
                $temp = strval($arg);
            }
            $temps[] = $temp;
        }
        $data = [];
        $data['data'] = $temps;
        $data['file'] = $backtrace_message;
        $data['act'] = $type;
        if ($time !== null) {
            $data['time'] = $time;
        }

        try {
            if (self::$sock === null) {
                self::$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            }
            $sock = self::$sock;
            $msg = json_encode($data);
            $len = strlen($msg);
            socket_sendto($sock, $msg, $len, 0, self::$log_udp_ip, self::$log_udp_port);
            //socket_close($sock);
        } catch (\Exception $e) {

        } catch (\Error $e) {

        }
    }

    /**
     * 获取属性
     * @param \ReflectionProperty $property
     * @return string
     */
    private static function getPropertyKey(\ReflectionProperty $property)
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

    /**
     * 对象解析出来以便可以json输出
     * @param $object
     * @return array
     * @throws \ReflectionException
     */
    private static function convert($object)
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
            $object_as_array[$key] = self::convert($value);
        }
        $reflection = new \ReflectionClass($object);
        foreach ($reflection->getProperties() as $property) {
            if (array_key_exists($property->getName(), $object_vars)) {
                continue;
            }
            $type = self::getPropertyKey($property);
            if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
                $property->setAccessible(true);
            }
            try {
                $value = $property->getValue($object);
            } catch (\ReflectionException $e) {
                $value = 'only PHP 5.3 can access private/protected properties';
            }
            if ($value === $object || in_array($value, $_processed, true)) {
                $value = 'recursion - parent object [' . get_class($value) . ']';
            }
            $object_as_array[$type] = self::convert($value);
        }
        return $object_as_array;
    }

    /**
     * 输出信息
     * @param mixed ...$args
     */
    public static function log(...$args)
    {
        self::send('log', $args);
    }

    /**
     * 输出错误信息
     * @param mixed ...$args
     */
    public static function error(...$args)
    {
        self::send('error', $args);
    }

    /**
     * 警告信息
     * @param mixed ...$args
     */
    public static function warn(...$args)
    {
        self::send('warn', $args);
    }

    /**
     * 信息
     * @param mixed ...$args
     */
    public static function info(...$args)
    {
        self::send('info', $args);
    }

    /**
     * @param string $sql
     * @param float $time 执行时间
     */
    public static function sql(string $sql, float $time)
    {
        self::send('sql', [$sql], round($time, 6));
    }
}