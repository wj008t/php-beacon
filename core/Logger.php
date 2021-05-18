<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-11-14
 * Time: 下午12:39
 */

namespace beacon\core;

/**
 * 日志管理类
 * Class Logger
 * @package beacon
 */
class Logger
{

    public static string $addr = "127.0.0.1";
    public static int $port = 1024;
    private static \Socket|null $sock = null;
    private static ?array $logSave = null;


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
        try {
            $backtrace = debug_backtrace(false);
            $backtrace_message = 'unknown';
            if (isset($backtrace[1]) && isset($backtrace[1]['file']) && isset($backtrace[1]['line'])) {
                $backtrace_message = $backtrace[1]['file'] . '(' . $backtrace[1]['line'] . ')';
            }
            $temps = [];
            foreach ($args as $arg) {
                $arg = self::convert($arg);
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
            if (self::$sock === null) {
                self::$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            }
            $sock = self::$sock;
            $msg = json_encode($data);
            $send = [];
            $msgId = md5(uniqid(microtime() . mt_rand()));
            $msgId = '--data--' . substr($msgId, 0, 24);
            $size = 4 * 1024;
            while (strlen($msg) > $size) {
                $t = substr($msg, 0, $size);
                $msg = substr($msg, $size);
                $send[] = $t;
            }
            if (strlen($msg) > 0) {
                $send[] = $msg;
            }
            $count = count($send);
            $head = $msgId;
            $head .= pack('n', $count);
            foreach ($send as $idx => $it) {
                $str = $head . pack('n', $idx) . $it;
                $len = strlen($str);
                @socket_sendto($sock, $str, $len, 0, self::$addr, self::$port);
            }
        } catch (\Exception $e) {

        }
    }

    /**
     * 获取属性
     * @param \ReflectionProperty $property
     * @return string
     */
    private static function propertyType(\ReflectionProperty $property): string
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
        return '';
    }

    /**
     * 对象解析出来以便可以json输出
     * @param $object
     * @return array|string|object|null
     * @throws \Exception
     */
    private static function convert($object): array|string|object|null
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
            $type = self::propertyType($property);
            $property->setAccessible(true);
            $value = $property->getValue($object);
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

    /**
     * 输出数据
     * @param string $text
     * @param string $color
     * @param bool $newLine
     */
    private static function out(string $text, string $color = 'info', bool $newLine = true)
    {
        $styles = array(
            'file' => "\033[0;33m%s\033[0m",
            'sql1' => "\033[0;34m%s\033[0m",
            'sql2' => "\033[0;37m%s\033[0m",
            'error' => "\033[31;31m%s\033[0m",
            'warn' => "\033[31;33m%s\033[0m",
            'info' => "\033[33;37m%s\033[0m"
        );
        $format = '%s';
        if (isset($styles[$color])) {
            $format = $styles[$color];
        }
        if ($newLine) {
            $format .= PHP_EOL;
        }
        printf($format, $text);
    }

    private static function save(string $text, string $act = 'info', bool $file = false)
    {
        if (self::$logSave == null) {
            return;
        }
        $kind = self::$logSave['kind'] ?? 'all';
        $path = self::$logSave['path'];
        if (empty($path)) {
            return;
        }
        if ($kind != 'all' && substr_count($kind, $act) == 0) {
            return;
        }
        if ($file) {
            $text = 'file [' . date('H:i:s') . '] ' . $text . PHP_EOL;
        } else {
            $text = $act . ' [' . date('H:i:s') . '] ' . $text . PHP_EOL;
        }
        if (!is_dir($path)) {
            Util::makeDir($path);
        }
        $single = self::$logSave['single'] ?? false;
        if ($single) {
            $LogFile = Util::path($path, date('Ymd') . '.log');
        } else {
            $LogFile = Util::path($path, date('Ymd') . '.' . $act . '.log');
        }
        file_put_contents($LogFile, $text, FILE_APPEND);
    }

    /**
     * 输出调试数据
     * @param array $item
     */
    private static function debug(array $item)
    {
        static $tempFile = null;
        if (!is_array($item) || count($item) == 0) {
            return;
        }
        $act = empty($item['act']) ? 'log' : $item['act'];
        $file = $item['file'] ?? null;
        $data = $item['data'] ?? null;
        $time = $item['time'] ?? null;
        if ($file && $tempFile != $file) {
            $tempFile = $file;
            self::out('> ' . $file, 'file', true);
            self::save('> ' . $file, $act, true);
        }
        if ($data !== null && is_array($data) && count($data) > 0) {
            if ($act == 'sql') {
                if ($time !== null) {
                    $time = number_format($time, 4, '.', '');
                    self::out($time . 's | ', 'sql2', false);
                    self::out($data[0], 'sql1', true);
                    self::save($time . 's | ' . $data[0], $act);
                } else {
                    self::out($data[0], 'sql1', true);
                    self::save($data[0], $act);
                }
            } else {
                foreach ($data as &$datum) {
                    if (is_array($datum)) {
                        $datum = json_encode($datum, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    }
                }
                self::out(join('    ', $data), $act, true);
                self::save(join('    ', $data), $act);
            }
        }
    }

    /**
     * 解包数据
     * @param string $msg
     */
    private static function unpack(string $msg)
    {

        static $msgMap = [];
        static $lTime = 0;
        $valid = substr($msg, 0, 8);
        if ($valid != '--data--') {
            return;
        }
        if ($lTime < time()) {
            $msgMap = [];
        }
        $lTime = time() + 10;
        $msgId = substr($msg, 8, 24);
        $count = substr($msg, 32, 2);
        $index = substr($msg, 34, 2);
        $count = unpack('n', $count)[1];
        $index = unpack('n', $index)[1];
        $msg = substr($msg, 36);
        if (!isset($msgMap[$msgId])) {
            $msgMap[$msgId] = [];
        }
        $msgMap[$msgId][$index . ''] = $msg;
        if (count($msgMap[$msgId]) == $count) {
            $bigMsg = [];
            for ($i = 0; $i < $count; $i++) {
                $bigMsg[] = $msgMap[$msgId][$i . ''];
            }
            $fMsg = join('', $bigMsg);
            unset($msgMap[$msgId]);
            if (preg_match('@^[\{\[].*[\}\]]$@', $fMsg)) {
                $data = json_decode($fMsg, true);
                self::debug($data);
            }
        }
    }

    /**
     * 保持日志
     * @param string $path
     * @param string $kind
     * @param bool $single 是否单一文件
     */
    public static function saveLog(string $path = '', string $kind = 'all', bool $single = false)
    {
        if (empty($path)) {
            return;
        }
        self::$logSave = [
            'path' => $path,
            'kind' => $kind,
            'single' => $single
        ];
    }

    /**
     * 监听调试
     * @param bool $remove 是否开启远程调试
     */
    public static function listen(bool $remove = false, string $password = '')
    {
        if (PHP_SAPI != 'cli') {
            exit;
        }
        $socket = stream_socket_server('udp://0.0.0.0:' . self::$port, $errno, $errstr, STREAM_SERVER_BIND);
        echo <<< EOF
  ____
 |  _ \
 | |_) |   ___    __ _    ___    ___    _ __
 |  _ <   / _ \  / _` |  / __|  / _ \  | '_ \ 
 | |_) | |  __/ | (_| | | (__  | (_) | | | | |
 |____/   \___|  \__,_|  \___|  \___/  |_| |_|
==================debug server=================

EOF;
        $client = null;
        if (empty($password)) {
            $remove = false;
        }
        do {
            $msg = stream_socket_recvfrom($socket, 5120, 0, $peer);
            if ($msg === false) {
                usleep(10000);
                continue;
            }
            //转发到客户端
            if ($remove && substr($msg, 0, 10) == '--client--') {
                $pwd = substr($msg, 10, 32);
                if ($pwd == md5($password)) {
                    if ($client === null || $client[0] != $peer) {
                        echo 'client connect by ' . $peer . PHP_EOL;
                    }
                    $client = [];
                    $client[0] = $peer;
                    $client[1] = time() + 120;
                }
                continue;
            }
            //转发数据
            if ($remove && $client !== null && is_array($client) && isset($client[1]) && $client[1] > time()) {
                @stream_socket_sendto($socket, $msg, 0, $client[0]);
                continue;
            } else {
                $client = null;
            }
            self::unpack($msg);
        } while (true);
    }

    /**
     * 远程客户端
     * @param string $addr
     * @param int $port
     * @param string $password
     */
    public static function client(string $addr = '127.0.0.1', int $port = 1024, string $password = '')
    {
        if (PHP_SAPI != 'cli') {
            exit;
        }
        echo <<< EOF
  ____
 |  _ \
 | |_) |   ___    __ _    ___    ___    _ __
 |  _ <   / _ \  / _` |  / __|  / _ \  | '_ \ 
 | |_) | |  __/ | (_| | | (__  | (_) | | | | |
 |____/   \___|  \__,_|  \___|  \___/  |_| |_|
==================debug client=================

EOF;
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
        $msg = '--client--' . md5($password);
        $len = strlen($msg);
        $sendTime = 0;
        $f = '';
        $p = 0;
        do {
            $nowTime = time();
            if ($sendTime < $nowTime) {
                //发送心跳
                socket_sendto($socket, $msg, $len, 0, $addr, $port);
                $sendTime = $nowTime + 10;
            }
            $result = socket_recvfrom($socket, $data, 5120, 0, $f, $p);
            if ($result === false) {
                usleep(10000);
                continue;
            }
            self::unpack($data);
        } while (true);
    }

}