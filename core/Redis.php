<?php


namespace beacon\core;


use Predis\Client;

class Redis
{
    private static Client|\Redis|null $redis = null;

    /**
     * 获取redis 实例
     * @return Client|\Redis|null
     */
    public static function instance(): Client|\Redis|null
    {
        if (self::$redis) {
            return self::$redis;
        }
        $config = Config::get('redis.*');
        if (extension_loaded('redis')) {
            self::$redis = new \Redis();
            if (!empty($config['sock'])) {
                self::$redis->pconnect($config['sock']);
            } else {
                self::$redis->pconnect($config['host'], $config['port'], isset($config['timeout']) ? $config['timeout'] : 20);
            }
        } else {
            if (!empty($config['sock'])) {
                self::$redis = new Client($config['sock']);
            } else {
                self::$redis = new Client($config);
            }
        }
        return self::$redis;
    }

    /**
     * 静态转换
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        $redis = static::instance();
        return call_user_func_array([$redis, $name], $arguments);
    }

    /**
     * 设置缓存
     * @param string $key
     * @param mixed $value
     * @param int $time
     */
    public static function setCache(string $key, mixed $value, int $time = 60)
    {
        static::instance()->setex($key, $time, json_encode($value));
    }

    /**
     * 获取缓存
     * @param string $key
     * @return mixed
     */
    public static function getCache(string $key): mixed
    {
        if (static::instance()->exists($key)) {
            $ret = static::instance()->get($key);
            if ($ret !== null) {
                return json_decode($ret, true);
            }
        }
        return null;
    }

    /**
     * 检查缓存是否存在
     * @param string $key
     * @return bool
     */
    public static function existCache(string $key): bool
    {
        if (static::instance()->exists($key)) {
            return true;
        }
        return false;
    }

    /**
     * 缓存运行
     * @param string $key
     * @param int $time
     * @param callable $func
     * @return mixed
     */
    public static function callCache(string $key, int $time, callable $func): mixed
    {
        if ($time == 0) {
            return $func();
        }
        $ret = static::getCache($key);
        if ($ret !== null) {
            return $ret;
        }
        $ret = $func();
        static::setCache($key, $ret, $time);
        return $ret;
    }


}