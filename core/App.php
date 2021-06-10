<?php

namespace beacon\core;

use sdopx\SdopxException;

if (!defined('ROOT_DIR')) {
    die('未定义ROOT_DIR目录');
}

defined('DEV_DEBUG') or define('DEV_DEBUG', false);
defined('DEBUG_LOG') or define('DEBUG_LOG', false);
defined('IS_CGI') or define('IS_CGI', substr(PHP_SAPI, 0, 3) == 'cgi');
defined('IS_CLI') or define('IS_CLI', PHP_SAPI == 'cli');
defined('IS_WIN') or define('IS_WIN', strstr(PHP_OS, 'WIN'));

set_error_handler(function (int $errno, string $errStr, string $errFile, int $errLine) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    if ($errno == E_WARNING || $errno == E_PARSE || $errno == E_NOTICE) {
        throw new \ErrorException($errStr, 0, $errno, $errFile, $errLine);
    }
    return true;
});

class App
{

    /**
     * @var array
     */
    protected static array $adapter = [
        'target' => null,
        'error' => [],
    ];
    /**
     * @var array<string,string>
     */
    protected static array $cachePath = [];
    /**
     * @var array<string,string>
     */
    protected static array $cacheUris = [];
    /**
     * @var array<Route>
     */
    protected static array $routeMap = [];

    /**
     * @var array<string,string>
     */
    public static array $routed = [];

    /**
     * 注册扩展
     * @param string $type
     * @param callable $func
     */
    public static function adapter(string $type, callable $func)
    {
        switch ($type) {
            case 'error':
                array_unshift(static::$adapter[$type], $func);
                break;
            case 'target':
                static::$adapter[$type] = $func;
                break;
            default:
                break;
        }
    }

    /**
     * 注册路由
     * @param Route $route
     */
    public static function reg(Route $route)
    {
        $name = $route->getName();
        static::$routeMap[$name] = $route;
        uasort(static::$routeMap, function (Route $a, Route $b) {
            $la = strlen($a->getBase());
            $lb = strlen($b->getBase());
            if ($la == $lb) {
                return 0;
            }
            return $la > $lb ? -1 : 1;
        });
    }

    /**
     * @param string $name
     * @param string $base
     * @param string $namespace
     */
    public static function route(string $name, string $base = '', string $namespace = '')
    {
        $route = new Route($name, $base, $namespace);
        $route->addRule([
            '@^/(\w+)/(\w+)$@i' => ['ctl' => '$1', 'act' => '$2'],
            '@^/(\w+)/?$@i' => ['ctl' => '$1', 'act' => 'index'],
            '@^/$@' => ['ctl' => 'index', 'act' => 'index'],
        ]);
        static::reg($route);
    }

    /**
     * 选择路由
     * @param string $url
     * @param ?string $uri
     * @return Route|null
     */
    protected static function matchRoute(string $url, ?string &$uri = ''): ?Route
    {
        foreach (static::$routeMap as $name => $item) {
            $uri = $item->getURI($url);
            if ($uri !== null) {
                return $item;
            }
        }
        return null;
    }

    /**
     * 解析URL
     * @param string $url
     * @return bool
     */
    protected static function parseUrl(string $url): bool
    {
        $temp = parse_url($url);
        $url = $temp['path'];
        if (preg_match('@\.json$@i', $url)) {
            $_SERVER['REQUEST_AJAX'] = 1;
            $url = preg_replace('@\.json$@i', '', $url);
        }
        $route = static::matchRoute($url, $uri);
        if ($route === null) {
            return false;
        }
        static::$routed['app'] = $route->getName();
        static::$routed['base'] = $route->getBase();
        $arg = ['ctl' => '', 'act' => ''];
        $rule = $route->getRules();
        foreach ($rule as $pattern => $map) {
            if (!is_array($map)) {
                continue;
            }
            if (!preg_match($pattern, $uri, $m)) {
                continue;
            }
            foreach ($map as $key => $val) {
                if (is_string($val)) {
                    $temp = preg_replace_callback('@\$(\d+)@', function ($m2) use ($m) {
                        return $m[$m2[1]] ?? '';
                    }, $val);
                    $arg[$key] = $temp;
                }
            }
        }
        if (empty($arg['ctl'])) {
            return false;
        }
        static::$routed['ctl'] = strtolower($arg['ctl']);
        static::$routed['act'] = strtolower($arg['act']);
        unset($arg['act']);
        unset($arg['ctl']);
        foreach ($arg as $key => $val) {
            if (!isset($_GET[$key])) {
                $_GET[$key] = $val;
            }
            if (!isset($_REQUEST[$key])) {
                $_REQUEST[$key] = $val;
            }
        }
        return true;
    }

    /**
     * @param string|null $url
     * @return bool
     */
    public static function parse(?string &$url = null): bool
    {
        if ($url !== null) {
            if (empty($url)) {
                $url = '/';
            }
            return static::parseUrl($url);
        }
        if (!IS_CLI) {
            if (isset($_SERVER['PATH_INFO'])) {
                $url = $_SERVER['PATH_INFO'];
            } else {
                $url = $_SERVER['REQUEST_URI'];
            }
            if (empty($url)) {
                $url = '/';
            }
            return static::parseUrl($url);
        }
        //命令行模式下
        if (!isset($_SERVER['argv']) || empty($_SERVER['argv'][1])) {
            $url = $_SERVER['REQUEST_URI'] = '/';
            return static::parseUrl($url);
        }
        $url = $_SERVER['REQUEST_URI'] = $_SERVER['argv'][1];
        if (!static::parseUrl($url)) {
            return false;
        }
        $data = parse_url($url);
        if (isset($data['query'])) {
            parse_str($data['query'], $args);
            foreach ($args as $key => $val) {
                $_GET[$key] = $val;
                $_REQUEST[$key] = $val;
            }
        }
        return true;

    }

    /**
     * 获取路由信息
     * @param string|null $url
     * @return array<string>|null
     * @throws RouteError
     */
    protected static function getRouteMap(?string $url = null): array|null
    {
        if (!static::parse($url)) {
            return null;
        }
        if (empty(static::$routed['app']) || empty(static::$routed['ctl']) || empty(static::$routed['act'])) {
            throw new RouteError('路由失败,url:' . $url);
        }
        $app = static::$routed['app'];
        $route = static::$routeMap[$app] ?? null;
        if ($route === null) {
            throw new RouteError('路由失败,url:' . $url);
        }
        $data = $route->getMap(static::$routed['ctl'], static::$routed['act']);
        if (!empty(static::$adapter['target']) && is_callable(static::$adapter['target'])) {
            $data = call_user_func(static::$adapter['target'], $data, static::$routed);
        }
        return $data;
    }

    /**
     * 记录运行时间
     */
    protected static function runTime()
    {
        if (defined('DEV_DEBUG') && DEV_DEBUG && defined('DEBUG_LOG') && DEBUG_LOG) {
            error_reporting(E_ALL);
            //程序计时---
            if (isset($_SERVER['REQUEST_URI'])) {
                $t1 = microtime(true);
                register_shutdown_function(function () use ($t1) {
                    $t2 = microtime(true);
                    Logger::info('URL:', $_SERVER['REQUEST_URI'], '耗时' . round($t2 - $t1, 3) . '秒');
                });
            }
        }
    }

    /**
     * 运行
     * @param string|null $url
     */
    public static function run(string $url = null)
    {
        static::runTime();
        try {
            $data = static::getRouteMap($url);
            if ($data === null) {
                return;
            }
            //启动脚本
            $startup = $data['namespace'] . '\\StartUp';
            if (class_exists($startup)) {
                if (is_callable([$startup, 'init'])) {
                    call_user_func([$startup, 'init']);
                }
                $data = array_merge(self::$routed, $data);
                if (is_callable([$startup, 'execute'])) {
                    call_user_func([$startup, 'execute'], $data);
                    return;
                }
            }
            $classFullName = $data['classFullName'];
            if (!class_exists($classFullName)) {
                throw new RouteError('没有找到控制器信息' . $data['className']);
            }
            static::executeMethod($classFullName, $data['method']);
        } catch (RouteError | \Exception $exception) {
            static::rethrow($exception);
        } catch (\Error $error) {
            static::rethrow($error);
        }
    }

    /**
     * @param string $class
     * @param string $action
     * @throws RouteError
     * @throws \ReflectionException
     */
    public static function executeMethod(string $class, string $action)
    {
        $refClass = new \ReflectionClass($class);
        $methods = $refClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        $cMethod = null;
        $reqMethod = Request::method();
        foreach ($methods as $item) {
            if ($item->isStatic()) {
                continue;
            }
            $attributes = $item->getAttributes(Method::class);
            if (count($attributes) == 0) {
                continue;
            }
            $rMethod = $attributes[0]->newInstance();
            if (!$rMethod->auth($action, $reqMethod)) {
                continue;
            }
            $cMethod = $item;
            break;
        }
        if ($cMethod === null) {
            throw new RouteError('未存在方法:' . $action);
        }
        //获取方法参数---
        $params = $cMethod->getParameters();
        $args = [];
        if (isset($params[0])) {
            foreach ($params as $param) {
                //获取变量名称
                $name = $param->getName();
                $type = '';
                //如果可以获取类型
                if ($param->hasType()) {
                    $refType = $param->getType();
                    if ($refType != null) {
                        if ($refType instanceof \ReflectionUnionType) {
                            $type = strval($refType);
                        } else {
                            $type = $refType->getName();
                        }
                    }
                }
                //默认值
                $default = null;
                //如果有默认值,从默认值中获取类型
                if ($param->isOptional()) {
                    $default = $param->getDefaultValue();
                    if ($type == '') {
                        $type = gettype($default);
                    }
                }
                $args[] = Request::lookType($_REQUEST, $name, $type, $default);
            }
        }
        $example = new $class();
        $out = $cMethod->invokeArgs($example, $args);
        if (is_array($out)) {
            Request::setContentType('json');
            die(json_encode($out, JSON_UNESCAPED_UNICODE));
        } else if (is_string($out)) {
            Request::setContentType('html');
            die($out);
        }
    }

    /**
     * @param \Throwable $exception
     */
    public static function rethrow(\Throwable $exception)
    {
        $out = [];
        $out['status'] = false;
        $out['_code'] = 500;
        $code = [];
        $code[] = get_class($exception) . ": {$exception->getMessage()}";
        $code[] = $exception->getTraceAsString();
        if (is_callable([$exception, 'getDetail'])) {
            $code[] = "--------------------------------------------";
            $code[] = $exception->getDetail();
        }
        //开启日志
        if ((defined('DEBUG_LOG') && DEBUG_LOG)) {
            Logger::error(join("\n", $code));
        }
        //如果开启调试,打印更详细的栈信息.
        if ((defined('DEV_DEBUG') && DEV_DEBUG)) {
            $out['_stack'] = explode("\n", join("\n", $code));
            if ($exception instanceof RouteError) {
                $out['msg'] = '404 页面没有找到:' . $exception->getMessage();
            } else {
                $out['msg'] = '数据出现异常:' . $exception->getMessage();
            }
        } else {
            if ($exception instanceof RouteError) {
                $out['msg'] = '404 页面没有找到!';
            } else {
                $out['msg'] = '数据出现异常,请稍后再试.';
            }
        }
        if (Request::isAjax()) {
            Request::setContentType('json');
            die(json_encode($out, JSON_UNESCAPED_UNICODE));
        }
        //输出错误页面--------
        Request::setContentType('html');
        $view = new View();
        $view->assign('info', $out);
        $template = Config::get('beacon.exception_template', '@exception.tpl');
        $view->display($template);
        exit;
    }

    /**
     * 获取当前路径
     * @param string $app
     * @return string
     */
    public static function getNamespace(string $app = ''): string
    {
        if (empty($app)) {
            $app = static::$routed['app'] ?? '';
        }
        if (empty($app)) {
            return '';
        }
        $route = static::$routeMap[$app] ?? null;
        if ($route === null) {
            return '';
        }
        $namespace = $route->getNamespace();
        if (empty($namespace)) {
            return '';
        }
        return $namespace;
    }

    /**
     * 获取路由数据
     * @param string $name
     * @return string|array
     */
    public static function get(string $name = ''): string|array
    {
        if (empty(static::$routed['app'])) {
            if (empty($name)) {
                return [];
            }
            return '';
        }
        if (empty($name)) {
            return static::$routed;
        }
        if (isset(static::$routed[$name])) {
            return static::$routed[$name];
        }
        return '';
    }

    /**
     * 替换URL
     * @param string $url
     * @param array $query
     * @param string $ext
     * @return string
     */
    protected static function replaceURL(string $url, array $query, string $ext): string
    {
        $url = preg_replace_callback('@{(\w+)}@', function ($m) use ($query) {
            $key = $m[1];
            return isset($query[$key]) ? urlencode($query[$key]) : '';
        }, $url);
        if (!empty($ext)) {
            $data = parse_url($url);
            $url = $data['path'] . '.' . $ext;
            if (!empty($data['query'])) {
                $url .= '?' . $data['query'];
            }
        }
        return $url;
    }

    /**
     * 计算HASH
     * @param string $pathname
     * @param array $query
     * @return string
     */
    private static function hashURL(string $pathname, array $query): string
    {
        //计算hash
        $temp = explode('/', $pathname);
        foreach ($temp as &$item) {
            if (isset($item[0])) {
                $item = Util::toUnder($item);
            }
        }
        $pathname = join('/', $temp);
        $hash = $pathname;
        if (count($query) > 0) {
            $temp = [];
            foreach ($query as $key => $val) {
                array_push($temp, $key . '={' . $key . '}');
            }
            $hash = $pathname . '?' . join('&', $temp);
        }
        return isset($hash[80]) ? md5($hash) : $hash;
    }

    /**
     * 获取缓存路径，并初始化URL缓存
     * @param string $app
     * @return string
     */
    protected static function loadCache(string $app): string
    {
        if (empty(static::$cachePath[$app])) {
            static::$cachePath[$app] = Util::path(ROOT_DIR, 'runtime', 'route.' . $app . '.cache.php');
        }
        $filepath = static::$cachePath[$app];
        //缓存的URL表
        if (!isset(static::$cacheUris[$app])) {
            if (file_exists($filepath)) {
                static::$cacheUris[$app] = require $filepath;
            } else {
                static::$cacheUris[$app] = [];
            }
        }
        return $filepath;
    }

    /**
     * 写入缓存
     * @param string $app
     * @param string $filepath
     */
    protected static function saveCache(string $app, string $filepath)
    {
        file_put_contents($filepath, '<?php return ' . var_export(static::$cacheUris[$app], true) . ';');
    }

    /**
     * 恢复URL
     * @param string $app
     * @param string $pathname
     * @param array $query
     * @return string
     */
    public static function resolve(string $app, string $pathname = '', array $query = []): string
    {
        if (empty($app)) {
            return '';
        }
        $ext = '';
        $pos = strrpos($pathname, '.');
        if ($pos !== false) {
            $ext = substr($pathname, $pos + 1);
            $pathname = substr($pathname, 0, $pos);
        }
        $filepath = static::loadCache($app);
        //计算hash
        $hash = static::hashURL($pathname, $query);
        //使用了缓存
        if (isset(static::$cacheUris[$app][$hash])) {
            $temp_url = static::$cacheUris[$app][$hash];
            return static::replaceURL($temp_url, $query, $ext);
        }
        //恢复URL
        $route = static::$routeMap[$app] ?? null;
        if ($route == null) {
            return '';
        }
        $base = $route->getBase();
        $base = rtrim($base, '/');
        $ctl = '';
        $act = '';
        //如果路径不为空
        if (!empty($pathname)) {
            if (preg_match('@^/?(\w+)(?:/(\w+))?@', $pathname, $mth)) {
                $ctl = Util::toUnder($mth[1]);
                if (isset($mth[2])) {
                    $act = Util::toUnder($mth[2]);
                }
            }
        }
        $args = [];
        foreach ($query as $key => $val) {
            $args[$key] = '{' . $key . '}';
        }
        $info = $route->resolve($ctl, $act, $args);
        $outUrl = preg_replace_callback('@{(ctl|act)}@', function ($m) use ($ctl, $act) {
            if ($m[1] == 'ctl') {
                return $ctl;
            }
            return $act;
        }, $info);
        if (preg_match_all('@{(\w+)}@', $outUrl, $mts)) {
            foreach ($mts[1] as $mt) {
                $key = $mt;
                unset($args[$key]);
            }
        }
        $queryStr = [];
        foreach ($args as $key => $val) {
            array_push($queryStr, $key . '={' . $key . '}');
        }
        $temp_url = $base . $outUrl;
        if (isset($queryStr[0])) {
            $temp_url .= '?' . join('&', $queryStr);
        }
        static::$cacheUris[$app][$hash] = $temp_url;
        static::saveCache($app, $filepath);
        return static::replaceURL($temp_url, $query, $ext);
    }

    /**
     * 生成URL
     * @param array|string $url
     * @param array $query
     * @return string
     */
    public static function url(array|string $url, array $query = []): string
    {
        if (is_array($url)) {
            $app = $url['app'] ?? static::get('app');
            $ctl = $url['ctl'] ?? static::get('ctl');
            $act = $url['act'] ?? '';
            $path = '/' . $ctl;
            if (!empty($act)) {
                $path .= '/' . $act;
            }
            unset($url['app']);
            unset($url['ctl']);
            unset($url['act']);
            if (is_array($query)) {
                $query = array_merge($url, $query);
            } else {
                $query = $url;
            }
            return static::resolve($app, $path, $query);
        }
        if (!is_string($url)) {
            return $url;
        }
        $isInner = (isset($url[1]) && ($url[0] == '~' || $url[0] == '^') && $url[1] == '/');
        if (!$isInner) {
            if ($query == null || !isset($query[0])) {
                return $url;
            }
        }
        $info = parse_url($url);
        $path = $info['path'] ?? '';
        $str_query = $info['query'] ?? '';
        $query = is_array($query) ? $query : [];
        //合并参数
        if (!empty($str_query)) {
            parse_str($str_query, $temp);
            $query = array_merge($temp, $query);
        }
        if (!$isInner) {
            $str_query = http_build_query($query);
            if (!empty($str_query)) {
                return $path . '?' . $str_query;
            }
            return $path;
        }
        if ($url[0] == '~') {
            $app = static::get('app');
            $path = substr($path, 1);
            return static::resolve($app, $path, $query);
        }
        if (!preg_match('@^\^/(\w+)((?:/\w+){1,2})?(\.\w+)?$@', $path, $data)) {
            return $url;
        }
        $app = $data[1] ?? static::get('app');
        $path = $data[2] ?? static::get('path');
        $path = empty($path) ? '/' : $path;
        if (isset($data[3])) {
            $path .= $data[3];
        }
        return static::resolve($app, $path, $query);
    }


}