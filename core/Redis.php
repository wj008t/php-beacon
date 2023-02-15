<?php


namespace beacon\core;


use Predis\Client;

class Redis
{
    private static Client|\Redis|null $redis = null;

    /**
     * 获取redis 实例
     * @return Client|\Redis|null
     * @throws CacheException
     */
    public static function instance(): Client|\Redis|null
    {
        try {
            if (self::$redis) {
                return self::$redis;
            }
            $config = Config::get('redis.*');
            if (extension_loaded('redis')) {
                self::$redis = new \Redis();
                if (!empty($config['sock'])) {
                    self::$redis->pconnect($config['sock']);
                } else {
                    self::$redis->pconnect($config['host'], $config['port'], $config['timeout'] ?? 20);
                }
                if (!empty($config['password'])) {
                    self::$redis->auth($config['password']);
                }
                if (!empty($config['database'])) {
                    self::$redis->select($config['database']);
                }
            } else {
                if (!empty($config['sock'])) {
                    self::$redis = new Client($config['sock']);
                } else {
                    self::$redis = new Client($config);
                }
            }
            return self::$redis;
        } catch (\Exception $e) {
            throw new CacheException($e->getMessage());
        }
    }

    /**
     * 静态转换
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws CacheException
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
     * @throws CacheException
     */
    public static function setCache(string $key, mixed $value, int $time = 60): void
    {
        try {
            static::instance()->setex($key, $time, json_encode($value, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            throw new CacheException($e->getMessage());
        }
    }

    /**
     * 获取缓存
     * @param string $key
     * @return mixed
     * @throws CacheException
     */
    public static function getCache(string $key): mixed
    {
        try {
            if (static::instance()->exists($key)) {
                $ret = static::instance()->get($key);
                if ($ret !== null) {
                    return json_decode($ret, true);
                }
            }
            return null;
        } catch (\Exception $e) {
            throw new CacheException($e->getMessage());
        }
    }

    /**
     * 检查缓存是否存在
     * @param string $key
     * @return bool
     * @throws CacheException
     */
    public static function existCache(string $key): bool
    {
        return static::exists($key);
    }

    /**
     * 检查重复
     * @param string|array $key
     * @return bool
     * @throws CacheException
     */
    public static function exists(string|array $key): bool
    {
        try {
            if (static::instance()->exists($key)) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            throw new CacheException($e->getMessage());
        }
    }

    /**
     * 设置缓存
     * @param string $key
     * @param int $expire
     * @param string $value
     * @return bool|\Redis
     * @throws CacheException
     */
    public static function setex(string $key, int $expire, string $value): bool|\Redis
    {
        try {
            return static::instance()->setex($key, $expire, $value);
        } catch (\Exception $e) {
            throw new CacheException($e->getMessage());
        }
    }

    /**
     * 获取数据内容
     * @param string $key
     * @return mixed
     * @throws CacheException
     */
    public static function get(string $key): mixed
    {
        try {
            return static::instance()->get($key);
        } catch (\Exception $e) {
            throw new CacheException($e->getMessage());
        }
    }

    /**
     * 获取数据内容
     * @param string $key
     * @return mixed
     * @throws CacheException
     */
    public static function del(string $key): mixed
    {
        try {
            return static::instance()->del($key);
        } catch (\Exception $e) {
            throw new CacheException($e->getMessage());
        }
    }

    /**
     * 设置过期时间
     * @throws CacheException
     */
    public static function expire(string $key, int $ttl)
    {
        try {
            return static::instance()->expire($key, $ttl);
        } catch (\Exception $e) {
            throw new CacheException($e->getMessage());
        }
    }

    /**
     * 缓存运行
     * @param string $key
     * @param int $time
     * @param callable $func
     * @return mixed
     * @throws CacheException
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