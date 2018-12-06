<?php

namespace beacon;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/11
 * Time: 1:43
 */
if (!defined('ROOT_DIR')) {
    die('未定义ROOT_DIR目录');
}
set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    if (0 === error_reporting()) {
        return false;
    }
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

defined('DEV_DEBUG') or define('DEV_DEBUG', false);
defined('DEBUG_LOG') or define('DEBUG_LOG', false);
defined('IS_CGI') or define('IS_CGI', substr(PHP_SAPI, 0, 3) == 'cgi' ? true : false);
defined('IS_CLI') or define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
defined('IS_WIN') or define('IS_WIN', strstr(PHP_OS, 'WIN') ? true : false);


class RouteError extends \Exception
{
}

class Route
{

    private static $cacheUris = null;
    private static $routeMap = [];
    private static $routePath = null;
    private static $cachePath = null;
    private static $route = null;

    private static $ctlPrefix = [];

    /**
     * 设置路由配置文件路径
     * @param string $path
     */
    public static function setRoutePath(string $path)
    {
        self::$routePath = Utils::path($path);
    }

    /**
     * 注册路由规则
     * @param string $name
     * @param array|bool $route
     */
    public static function register(string $name, $route = null)
    {
        if (empty($name)) {
            return;
        }
        //默认数据
        $map = [
            'path' => 'app/' . $name,
            'namespace' => 'app\\' . $name,
            'base' => '/' . ($name == 'home' ? '' : $name),
            'rules' => [
                '@^/(\w+)/(\w+)/(\d+)$@i' => [
                    'ctl' => '$1',
                    'act' => '$2',
                    'id' => '$3',
                ],
                '@^/(\w+)/(\d+)$@i' => [
                    'ctl' => '$1',
                    'act' => 'index',
                    'id' => '$2',
                ],
                '@^/(\w+)/(\w+)$@i' => [
                    'ctl' => '$1',
                    'act' => '$2',
                ],
                '@^/(\w+)/?$@i' => [
                    'ctl' => '$1',
                    'act' => 'index',
                ],
                '@^/$@' => [
                    'ctl' => 'index',
                    'act' => 'index',
                ],
            ],
            'resolve' => function ($ctl, $act, $keys = []) {
                $url = '/{ctl}';
                if (!empty($act) && $act != 'index') {
                    $url .= '/{act}';
                }
                if (isset($keys['id'])) {
                    $url .= '/{id}';
                }
                return $url;
            }
        ];

        if ($route === null) {
            if (empty(self::$routePath)) {
                self::$routePath = Utils::path(ROOT_DIR, 'config');
            }
            $filePath = Utils::path(self::$routePath, $name . '.route.php');
            if (file_exists($filePath)) {
                $map = require $filePath;
            }
        } else if (is_array($route)) {
            $map = array_merge($map, $route);
        }
        $map['name'] = $name;
        $map['base'] = rtrim(empty($map['base']) ? '' : $map['base'], '/');
        $map['base_match'] = '@^' . preg_quote($map['base'], '@') . '(/.*)?$@i';
        $map['path'] = Utils::trimPath($map['path']);
        self::$routeMap[$name] = $map;
    }

    /**
     * 提取uri
     * @param string $url
     * @return mixed|null
     */
    private static function matchUrl(string $url)
    {
        uasort(self::$routeMap, function ($a, $b) {
            if (strlen($a['base']) == strlen($b['base'])) {
                return 0;
            }
            return strlen($a['base']) > strlen($b['base']) ? -1 : 1;
        });
        foreach (self::$routeMap as $name => $item) {
            if (preg_match($item['base_match'], $url, $m)) {
                $item['uri'] = empty($m[1]) ? '' : $m[1];
                $item['uri'] = preg_replace('@^/index\.php@i', '/', $item['uri']);
                return $item;
            }
        }
        return null;
    }


    /**
     * 获取路由解析数据
     * @param null $name
     * @return null
     */
    public static function get($name = null)
    {
        if (self::$route == null) {
            return null;
        }
        if ($name == null) {
            return self::$route;
        }
        if (isset(self::$route[$name])) {
            return self::$route[$name];
        }
        return null;
    }


    /**
     * 解析URL路径
     * @param string $url
     * @return array|null
     */
    private static function parseUrl(string $url)
    {
        if ('/favicon.ico' == $url) {
            exit;
        }
        $url_temp = parse_url($url);
        $url = $url_temp['path'];
        if (preg_match('@\.json$@i', $url)) {
            $_SERVER['REQUEST_AJAX'] = true;
            $url = preg_replace('@\.json$@i', '', $url);
        }
        $iData = self::matchUrl($url);

        if ($iData == null) {
            return null;
        }
        //路由路径
        $uri = empty($iData['uri']) ? '/' : $iData['uri'];
        $name = $iData['name'];
        $arg = [
            'app' => $name,
            'base' => $iData['base'],
            'ctl' => '',
            'act' => '',
        ];
        if (!isset($iData['rules']) || !is_array($iData['rules'])) {
            return null;
        }
        foreach ($iData['rules'] as $preg => $item) {
            if (preg_match($preg, $uri, $m)) {
                if (!is_array($item)) {
                    continue;
                }
                foreach ($item as $key => $val) {
                    $temp = null;
                    if (is_string($val)) {
                        $temp = preg_replace_callback('@\$(\d+)@', function ($m2) use ($m) {
                            return isset($m[$m2[1]]) ? $m[$m2[1]] : '';
                        }, $val);
                    } elseif (is_array($val)) {
                        $temp = preg_replace_callback('@\$(\d+)@', function ($m2) use ($m, $val) {
                            return isset($m[$m2[1]]) ? $m[$m2[1]] : $val['def'];
                        }, $val['map']);
                    } else {
                        continue;
                    }
                    $arg[$key] = $temp;
                }
                break;
            }
        }
        if (empty($arg['ctl'])) {
            return null;
        }
        $arg['ctl'] = strtolower($arg['ctl']);
        $arg['act'] = strtolower($arg['act']);
        foreach ($arg as $key => $val) {
            if (in_array($key, ['act', 'ctl', 'base', 'app'])) {
                continue;
            }
            if (!isset($_GET[$key])) {
                $_GET[$key] = $val;
            }
            if (!isset($_REQUEST[$key])) {
                $_REQUEST[$key] = $val;
            }
        }
        self::$route = $arg;
        return $arg;
    }

    /**
     * 解析URL
     * @param null $url
     * @return null|string
     */
    public static function parse($url = null)
    {
        $request_uri = null;
        if ($url === null) {
            if (IS_CLI) {
                if (isset($_SERVER['argv']) && !empty($_SERVER['argv'][1])) {
                    $request_uri = $_SERVER['REQUEST_URI'] = $_SERVER['argv'][1];
                    self::parseUrl($_SERVER['argv'][1]);
                    $data = parse_url($_SERVER['REQUEST_URI']);
                    if (isset($data['query'])) {
                        parse_str($data['query'], $args);
                        foreach ($args as $key => $val) {
                            $_GET[$key] = $val;
                            $_REQUEST[$key] = $val;
                        }
                    }
                } else {
                    $request_uri = $_SERVER['REQUEST_URI'] = '/';
                    self::parseUrl('/');
                }
            } else {
                if (isset($_SERVER['PATH_INFO'])) {
                    $request_uri = $_SERVER['PATH_INFO'];
                } else {
                    $request_uri = $_SERVER['REQUEST_URI'];
                }
                if (empty($request_uri)) {
                    $request_uri = '/';
                }
                self::parseUrl($request_uri);
            }
        } else {
            $request_uri = $url;
            self::parseUrl($url);
        }

        return $request_uri;
    }


    /**
     * 获取当前应用目录
     * @param string|null $app
     * @return null|string
     */
    public static function getPath(string $app = null)
    {
        if ($app == null) {
            $app = self::get('app');
        }
        if (empty($app)) {
            return null;
        }
        $data = isset(self::$routeMap[$app]) ? self::$routeMap[$app] : [];
        if (empty($data['path'])) {
            return null;
        }
        $path = Utils::path(ROOT_DIR, $data['path']);
        return $path;
    }

    /**
     * 获取当前应用命名空间
     * @param string|null $app
     * @return mixed|null|string
     */
    public static function getNamespace(string $app = null)
    {
        if ($app == null) {
            $app = self::get('app');
        }
        if (empty($app)) {
            return null;
        }
        $data = isset(self::$routeMap[$app]) ? self::$routeMap[$app] : [];
        if (empty($data['path']) && empty($data['namespace'])) {
            return null;
        }
        $namespace = isset($data['namespace']) ? $data['namespace'] : $data['path'];
        $namespace = trim(str_replace(['/', '\\'], '\\', $namespace), '\\');
        return $namespace;
    }

    /**
     * 反解析URL
     * @param string $app
     * @param string $pathname
     * @param array $query
     * @return mixed|string
     */
    public static function resolve(string $app, string $pathname = '', array $query = [])
    {

        if (empty($app)) {
            return '';
        }
        if (!empty($query)) {
            $temp = [];
            foreach ($query as $key => $val) {
                array_push($temp, $key . '={' . $key . '}');
            }
            $hash = $pathname . '?' . join('&', $temp);
        } else {
            $hash = $pathname;
        }
        $hash = isset($hash[80]) ? md5($hash) : $hash;
        if (empty(self::$cachePath)) {
            self::$cachePath = Utils::path(ROOT_DIR, 'runtime');
        }
        $filepath = Utils::path(self::$cachePath, 'route.' . $app . '.cache.php');
        if (self::$cacheUris == null) {
            self::$cacheUris = [];
        }
        if (!isset(self::$cacheUris[$app])) {
            if (file_exists($filepath)) {
                self::$cacheUris[$app] = require $filepath;
            } else {
                self::$cacheUris[$app] = [];
            }
        }
        //使用了缓存
        if (isset(self::$cacheUris[$app][$hash])) {
            $temp_url = self::$cacheUris[$app][$hash];
            $temp_url = preg_replace_callback('@\{(\w+)\}@', function ($m) use ($query) {
                $key = $m[1];
                return isset($query[$key]) ? urlencode($query[$key]) : '';
            }, $temp_url);
            return $temp_url;
        }

        $idata = isset(self::$routeMap[$app]) ? self::$routeMap[$app] : null;
        if ($idata == null) {
            return '';
        }
        $ctl = '';
        $act = '';
        if (!empty($pathname)) {
            if (preg_match('@^\/?(\w+)(?:\/(\w+))?@', $pathname, $mth)) {
                $ctl = Utils::toUnder($mth[1]);
                if (isset($mth[2])) {
                    $act = Utils::toUnder($mth[2]);
                }
            }
        }
        $args = [];
        foreach ($query as $key => $val) {
            $args[$key] = '{' . $key . '}';
        }
        $base = rtrim(empty($idata['base']) ? '' : $idata['base'], '/');
        if (!isset($idata['resolve']) && !is_callable($idata['resolve'])) {
            return '';
        }
        $out_url = '';
        $info = $idata['resolve']($ctl, $act, $args);
        if (is_string($info)) {
            $out_url = preg_replace_callback('@\{(ctl|act)\}@', function ($m) use ($ctl, $act) {
                if ($m[1] == 'ctl') {
                    return $ctl;
                }
                if ($m[1] == 'act') {
                    return $act;
                }
            }, $info);
            if (preg_match_all('@\{(\w+)\}@', $out_url, $mts)) {
                foreach ($mts[1] as $mt) {
                    $key = $mt;
                    unset($args[$key]);
                }
            }
        } elseif (is_array($info)) {
            $out_url = preg_replace_callback('@\{(ctl|act)\}@', function ($m) use ($ctl, $act) {
                if ($m[1] == 'ctl') {
                    return $ctl;
                }
                if ($m[1] == 'act') {
                    return $act;
                }
            }, $info[0]);
            $args = $info[1];
            if (!isset($info[2]) || $info[2] == false) {
                if (preg_match_all('@\{(\w+)\}@', $out_url, $mts)) {
                    foreach ($mts[1] as $mt) {
                        $key = $mt;
                        unset($args[$key]);
                    }
                }
            }
        }
        $queryStr = [];
        foreach ($args as $key => $val) {
            array_push($queryStr, $key . '={' . $key . '}');
        }
        $temp_url = $base . $out_url;
        if (count($queryStr) > 0) {
            $temp_url .= '?' . join('&', $queryStr);
        }
        self::$cacheUris[$app][$hash] = $temp_url;
        @file_put_contents($filepath, '<?php return ' . var_export(self::$cacheUris[$app], true) . ';');
        // echo '创建缓存';
        $temp_url = preg_replace_callback('@\{(\w+)\}@', function ($m) use ($query) {
            $key = $m[1];
            return isset($query[$key]) ? urlencode($query[$key]) : '';
        }, $temp_url);
        return $temp_url;
    }

    /**
     * 获取生成的URL
     * ~/ctl/act
     * ^/admin/ctl/act
     * @param string $url
     * @param array $query
     * @return bool|mixed|null|string
     */
    public static function url($url = null, array $query = [])
    {
        if (is_array($url)) {
            $url['app'] = isset($url['app']) ? $url['app'] : self::get('app');
            $url['ctl'] = isset($url['ctl']) ? $url['ctl'] : self::get('ctl');
            $url['act'] = isset($url['act']) ? $url['act'] : self::get('act');
            $temp = '^' . $url['app'] . '/' . $url['ctl'] . '/' . $url['act'];
            $url = $temp;
        }
        if (!is_string($url)) {
            return $url;
        }
        $innerUri = (isset($url[1]) && ($url[0] == '~' || $url[0] == '^') && $url[1] == '/');
        if (!$innerUri) {
            if ($query == null || count($query) == 0) {
                return $url;
            }
        }
        $info = parse_url($url);
        $path = isset($info['path']) ? $info['path'] : '';
        $str_query = isset($info['query']) ? $info['query'] : '';
        $query = is_array($query) ? $query : [];
        //合并参数
        if (!empty($str_query)) {
            $temp = [];
            parse_str($str_query, $temp);
            $query = array_merge($temp, $query);
        }
        if (!$innerUri) {
            $str_query = http_build_query($query);
            if (!empty($str_query)) {
                return $path . '?' . $str_query;
            }
            return $path;
        }
        if ($url[0] == '~') {
            $app = self::get('app');
            $path = substr($path, 1);
            return self::resolve($app, $path, $query);
        }
        if (!preg_match('@^\^/(\w+)((?:/\w+){1,2})?$@', $path, $data)) {
            return $url;
        }
        $app = isset($data[1]) ? $data[1] : self::get('app');
        $path = isset($data[2]) ? $data[2] : self::get('path');
        $path = empty($path) ? '/' : $path;
        return self::resolve($app, $path, $query);
    }

    /**
     * 运行实例的方法
     * @param string $class
     * @param string $method
     * @throws RouteError
     * @throws \ReflectionException
     */
    public static function runMethod(string $class, string $method)
    {
        $oReflectionClass = new \ReflectionClass($class);
        //获取方法信息
        $method = $oReflectionClass->getMethod($method);
        if (!$method->isPublic()) {
            throw new RouteError('未公开方法:' . $method);
        }
        //获取方法参数
        $params = $method->getParameters();
        $args = [];
        if (count($params) > 0) {
            foreach ($params as $param) {
                //获取变量名称
                $name = $param->getName();
                $type = 'any';
                //如果可以获取类型
                if (is_callable([$param, 'hasType'])) {
                    if ($param->hasType()) {
                        $refType = $param->getType();
                        if ($refType != null) {
                            if (is_callable([$refType, 'getName'])) {
                                $type = $refType->getName();
                            } else {
                                $type = strval($refType);
                            }
                            $type = empty($type) ? 'any' : $type;
                        }
                    }
                }
                //类型获取不到
                if ($type == 'any') {
                    if (is_callable([$param, 'getClass'])) {
                        $refType = $param->getClass();
                        if ($refType != null) {
                            if (is_callable([$refType, 'getName'])) {
                                $type = $refType->getName();
                            } else {
                                $type = strval($refType);
                            }
                            $type = empty($type) ? 'any' : $type;
                        }
                    }
                }
                //默认值
                $def = null;
                //如果有默认值,从默认值中获取类型
                if ($param->isOptional()) {
                    $def = $param->getDefaultValue();
                    if ($type == 'any') {
                        $type = gettype($def);
                    }
                }
                switch ($type) {
                    case 'bool':
                    case 'boolean':
                        $args[] = Request::param($name . ':b', $def);
                        break;
                    case 'int':
                    case 'integer':
                        $val = Request::param($name . ':s', $def);
                        //如果默认值是整数,但是传递的值是浮点数
                        if (preg_match('@[+-]?\d*\.\d+@', $val)) {
                            $args[] = Request::param($name . ':f', $def);
                        } else {
                            $args[] = Request::param($name . ':i', $def);
                        }
                        break;
                    case 'double':
                    case 'float':
                        $args[] = Request::param($name . ':f', $def);
                        break;
                    case 'string':
                        $args[] = Request::param($name . ':s', $def);
                        break;
                    case 'array':
                        $args[] = Request::param($name . ':a', $def);
                        break;
                    default :
                        $args[] = Request::param($name, $def);
                        break;
                }
            }
        }
        $example = new $class();
        //如果有初始化方法
        if (method_exists($example, 'initialize')) {
            $example->initialize();
        }
        //调用方法
        $out = $method->invokeArgs($example, $args);
        if (Request::getContentType() == 'application/json' || Request::getContentType() == 'text/json') {
            die(json_encode($out, JSON_UNESCAPED_UNICODE));
        } else if (is_array($out)) {
            Request::setContentType('json');
            die(json_encode($out, JSON_UNESCAPED_UNICODE));
        } else if (!empty($out)) {
            Request::setContentType('html');
            echo $out;
        }
    }

    /**
     * 获取映射信息
     * @param string|null $url
     * @return array
     * @throws RouteError
     */
    public static function getMapping(string $url = null)
    {
        $url = self::parse($url);
        if (self::$route == null) {
            throw new RouteError('未初始化路由参数,url:' . $url);
        }
        if (empty(self::$route['app'])) {
            throw new RouteError('路由应用名称 app 为空,url:' . $url);
        }
        if (empty(self::$route['ctl'])) {
            throw new RouteError('路由控制器名称 ctl 为空,url:' . $url);
        }
        if (empty(self::$route['act'])) {
            throw new RouteError('路由方法名称 act 为空,url:' . $url);
        }

        $ctl = Utils::toCamel(self::$route['ctl']);
        $act = Utils::toCamel(self::$route['act']);
        $act = lcfirst($act);
        $appPath = self::getPath();
        if (empty($appPath)) {
            throw new RouteError('没有设置应用目录,url:' . $url);
        }
        //设置当前应用下的配置文件
        $config = Utils::path($appPath, 'config.php');
        if (file_exists($config)) {
            $cfgData = Config::loadFile($config);
            foreach ($cfgData as $key => $val) {
                Config::set($key, $val);
            }
        }
        //开始进入入口---------------------
        $namespace = self::getNamespace();
        return [
            'namespace' => $namespace,
            'classFullName' => $namespace . '\\controller\\' . $ctl,
            'className' => $ctl,
            'method' => $act . 'Action'
        ];
    }

    /**
     * 添加控制器路由前缀
     * @param string $app
     * @param string $prefix
     */
    public static function addCtlPrefix(string $app, string $prefix)
    {
        if (!isset(self::$ctlPrefix[$app])) {
            self::$ctlPrefix[$app] = [];
        }
        self::$ctlPrefix[$app][] = ltrim($prefix, '\\');
    }

    /**
     * 记录运行时间
     */
    public static function recordRunTime()
    {
        if (defined('DEBUG_LOG') && DEBUG_LOG) {
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
        self::recordRunTime();
        try {
            $data = self::getMapping($url);
            $app = self::$route['app'];
            if (!class_exists($data['classFullName']) && isset(self::$ctlPrefix[$app])) {
                foreach (self::$ctlPrefix[$app] as $prefix) {
                    $classFullName = $data['namespace'] . '\\' . $prefix . $data['className'];
                    if (class_exists($classFullName)) {
                        $data['classFullName'] = $classFullName;
                        break;
                    }
                }
            }
            self::runMethod($data['classFullName'], $data['method']);
        } catch (RouteError $exception) {
            self::rethrow($exception);
        } catch (\Exception $exception) {
            self::rethrow($exception);
        } catch (\Error $error) {
            self::rethrow($error);
        }
    }

    /**
     * 抛出错误
     * @param \Throwable $exception
     */
    public static function rethrow(\Throwable $exception)
    {
        $out = [];
        $out['status'] = false;
        //如果开启调试,打印更详细的栈信息.
        if ((defined('DEV_DEBUG') && DEV_DEBUG)) {
            $code = [];
            $code[] = get_class($exception) . ": {$exception->getMessage()}";
            $code[] = $exception->getTraceAsString();
            if (is_callable([$exception, 'getDetail'])) {
                $code[] = "----------------------------------------------------------------------------------------------------------";
                $code[] = $exception->getDetail();
            }
            //开启日志
            if ((defined('DEBUG_LOG') && DEBUG_LOG)) {
                Logger::error(join("\n", $code));
            }
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
    }
}

