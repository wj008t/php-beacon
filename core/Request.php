<?php

namespace beacon\core;


defined('USE_REDIS_SESSION') or define('USE_REDIS_SESSION', false);

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/11
 * Time: 20:44
 */
class Request
{

    const OVERRIDE = 'HTTP_X_HTTP_METHOD_OVERRIDE';

    //设置Session
    private static ?string $sessionId = null;
    private static ?array $sessionData = null;

    /**
     * 受信任的代理ip
     * @var string[]
     */
    protected static array $proxies = [];
    /**
     * @var string[][]
     */
    protected static array $formats = [
        'rdf' => 'application/rdf+xml',
        'atom' => 'application/atom+xml',
        'rss' => 'application/rss+xml',
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => ['text/html', 'application/xhtml+xml'],
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => ['application/javascript', 'application/x-javascript', 'text/javascript'],
        'json' => ['text/json', 'application/json', 'application/x-json'],
        'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',
        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',
        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    ];

    /**
     * 是否覆盖
     * @return bool
     */
    protected static function overridden(): bool
    {
        return isset($_POST[static::OVERRIDE]) || isset($_SERVER[static::OVERRIDE]);
    }

    /**
     * 数据输入格式化
     * @param array $input
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public static function lookup(array $input, string $name = '', $default = null): mixed
    {
        if (empty($name)) {
            return $input;
        }
        $type = '';
        if (preg_match('@^(.*):([abfis])$@', $name, $m)) {
            $type = $m[2];
            $name = $m[1];
        }
        return match ($type) {
            's' => static::lookType($input, $name, 'string', $default),
            'b' => static::lookType($input, $name, 'bool', $default),
            'f' => static::lookType($input, $name, 'float', $default),
            'i' => static::lookType($input, $name, 'int', $default),
            'a' => static::lookType($input, $name, 'array', $default),
            default => $input[$name] ?? $default,
        };
    }


    /**
     * 参数类型转换
     * @param array $input
     * @param string $name
     * @param string $type
     * @param mixed|null $default
     * @return mixed
     */
    public static function lookType(array $input, string $name, string $type, mixed $default = null): mixed
    {
        if (empty($type)) {
            return $input[$name] ?? $default;
        }
        $map = Util::typeMap($type);
        $type = array_shift($map);
        $noNil = !isset($map['null']);
        switch ($type) {
            case 'bool':
            case 'boolean':
                if (!isset($input[$name])) {
                    if ($default === null && $noNil) {
                        $default = false;
                    }
                    return $default;
                }
                return $input[$name] === '1' || $input[$name] === 'true';
            case 'int':
                if (!isset($input[$name])) {
                    if ($default === null && $noNil) {
                        $default = 0;
                    }
                    return $default;
                }
                $val = $input[$name];
                if (preg_match('@^[+-]?\d*\.\d+$@', $val)) {
                    if (isset($map['float']) || isset($map['double'])) {
                        return doubleval($val);
                    } elseif (isset($map['string'])) {
                        return $val;
                    }
                    return intval($val);
                } else if (is_numeric($val)) {
                    return intval($val);
                } else if (isset($map['string'])) {
                    return $val;
                }
                if ($default === null && $noNil) {
                    $default = 0;
                }
                return $default;
            case 'double':
            case 'float':
                if (!isset($input[$name])) {
                    if ($default === null && $noNil) {
                        $default = 0;
                    }
                    return $default;
                }
                $val = $input[$name];
                if (is_numeric($val) || preg_match('@^[+-]?\d*\.\d+$@', $val)) {
                    return floatval($val);
                } else if (isset($map['double'])) {
                    return $val;
                }
                if ($default === null && $noNil) {
                    $default = 0;
                }
                return $default;
            case 'string':
                if (!isset($input[$name])) {
                    if ($default === null && $noNil) {
                        $default = '';
                    }
                    return $default;
                }
                return $input[$name];
            case 'array':
                if (!isset($input[$name])) {
                    if ($default === null && $noNil) {
                        $default = [];
                    }
                    return $default;
                }
                if (is_array($input[$name])) {
                    return $input[$name];
                }
                $val = $input[$name];
                //JSON
                if (is_string($val) && Util::isJson($val)) {
                    return json_decode($val, true);
                }
                //拆分，取值
                if (is_string($val) && preg_match('@^\d+(,\d+)*$@', $val)) {
                    $values = [];
                    $temp = explode(',', $val);
                    foreach ($temp as $item) {
                        if (is_numeric($item)) {
                            $values[] = intval($item);
                        }
                    }
                    return $values;
                }
                return [];
            default :
                return $input[$name] ?? $default;
        }
    }

    /**
     *  get 获取数据 相当于 $_GET
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public static function get(string $name = '', $default = null): mixed
    {
        return static::lookup($_GET, $name, $default);
    }

    /**
     * post 获取数据 相当于 $_POST
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public static function post(string $name = '', $default = null): mixed
    {
        return static::lookup($_POST, $name, $default);
    }

    /**
     * param 获取数据 相当于 $_REQUEST
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public static function param(string $name = '', $default = null): mixed
    {
        return static::lookup($_REQUEST, $name, $default);
    }

    /**
     * 获取 input 内容
     * @param null $default
     * @return string|null
     */
    public static function body($default = null): string|null
    {
        return file_get_contents('php://input') ?: $default;
    }

    /**
     * 接受流数据
     * @param $key
     * @param $default
     * @return mixed
     */
    protected static function stream($key, $default): mixed
    {
        if (Request::overridden()) {
            return static::lookup($_POST, $key, $default);
        }
        parse_str(file_get_contents('php://input'), $input);
        return static::lookup($input, $key, $default);
    }

    /**
     * 获取DELETE请求的参数
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public static function delete(string $name = '', $default = null): mixed
    {
        return static::method() === 'DELETE' ? static::stream($name, $default) : $default;
    }

    /**
     * 获取PUT请求的参数
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public static function put(string $name = '', $default = null): mixed
    {
        return static::method() === 'PUT' ? static::stream($name, $default) : $default;
    }

    /**
     * 服务器参数
     * @param string $key
     * @param null $default
     * @return string|array|null
     */
    public static function server(string $key = '', $default = null): string|array|null
    {
        return static::lookup($_SERVER, $key, $default);
    }


    /**
     * 获取请求协议
     * @param string $default
     * @return string
     */
    public static function protocol(string $default = 'HTTP/1.1'): string
    {
        return static::server('SERVER_PROTOCOL', $default);
    }

    /**
     * 设置受信任ip
     * @param array $proxies
     */
    public static function proxies(array $proxies): void
    {
        static::$proxies = $proxies;
    }

    /**
     * 添加格式
     * @param string $format
     * @param string|array $types
     */
    public static function format(string $format, string|array $types)
    {
        static::$formats[$format] = is_array($types) ? $types : [$types];
    }

    /**
     * 格式化头信息
     * @param $terms
     * @param $regex
     * @return array
     */
    protected static function parse(string $terms, string $regex): array
    {
        $result = [];
        foreach (array_reverse(explode(',', $terms)) as $part) {
            if (preg_match("/{$regex}/", $part, $m)) {
                $quality = $m['quality'] ?? 1;
                $result[$m['term']] = $quality;
            }
        }
        arsort($result);
        return array_keys($result);
    }

    /**
     * 获取首选语言
     * @param string $default
     * @return string
     */
    public static function language(string $default = ''): string
    {
        $languages = static::languages();
        if (isset($languages[0])) {
            return $languages[0];
        }
        return $default;
    }

    /**
     * 获取所有语言
     * @return string[]
     */
    public static function languages(): array
    {
        return static::parse(
            static::server('HTTP_ACCEPT_LANGUAGE', 'en'),
            '(?P<term>[\w\-]+)+(?:;q=(?P<quality>[0-9]+\.[0-9]+))?'
        );
    }

    /**
     * 获取媒体类型
     * @param string $type
     * @param false $strict
     * @return string
     */
    protected static function media(string $type, bool $strict = false): string
    {
        if ($strict) {
            return $type;
        }
        $type = preg_split('/\s*;\s*/', $type)[0];
        foreach (static::$formats as $format => $types) {
            if (is_array($types) && in_array($type, $types)) {
                return $format;
            } else if (is_string($types) && $type == $types) {
                return $format;
            }
        }
        return $type;
    }

    /**
     * 获取类型
     * @param string|null $default
     * @param false $strict
     * @return string
     */
    public static function type(?string $default = null, bool $strict = false): string
    {
        $type = static::server('HTTP_CONTENT_TYPE', $default ?: 'application/x-www-form-urlencoded');
        return static::media($type, $strict);
    }

    /**
     * 获取浏览器客户端代理信息
     * @param string $default
     * @return string
     */
    public static function agent(string $default = ''): string
    {
        return static::server('HTTP_USER_AGENT', $default);
    }

    /**
     * 受信任
     * @return bool
     */
    public static function entrusted(): bool
    {
        return (empty(static::$proxies) || isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], static::$proxies));
    }

    /**
     * 是否HTTPS
     * @return bool
     */
    public static function secure(): bool
    {
        if (strtoupper(static::server('HTTPS')) == 'ON')
            return true;
        if (!static::entrusted()) return false;
        return (strtoupper(static::server('SSL_HTTPS')) == 'ON' ||
            strtoupper(static::server('X_FORWARDED_PROTO')) == 'HTTPS');
    }

    /**
     * 获取HOST信息
     * @param string $default
     * @return string
     */
    public static function host(string $default = ''): string
    {
        $keys = ['HTTP_HOST', 'SERVER_NAME', 'SERVER_ADDR'];
        if (static::entrusted() &&
            $host = static::server('X_FORWARDED_HOST')) {
            $host = explode(',', $host);
            $host = trim($host[count($host) - 1]);
        } else {
            foreach ($keys as $key) {
                if (isset($_SERVER[$key])) {
                    $host = $_SERVER[$key];
                    break;
                }
            }
        }
        return isset($host) ? preg_replace('/:\d+$/', '', $host) : $default;
    }

    /**
     * 获取端口
     * @param bool $decorated
     * @return int|string
     */
    public static function port(bool $decorated = false): int|string
    {
        $port = static::entrusted() ? static::server('X_FORWARDED_PORT') : null;
        $port = $port ?: static::server('SERVER_PORT');
        return $decorated ? (in_array($port, [80, 443]) ? '' : ":$port") : $port;
    }

    /**
     * 获取 scheme 信息
     * @param bool $decorated
     * @return string
     */
    public static function scheme(bool $decorated = false): string
    {
        $scheme = static::secure() ? 'https' : 'http';
        return $decorated ? "$scheme://" : $scheme;
    }

    /**
     * 获取请求方式
     * @return string
     */
    public static function method(): string
    {
        $method = static::overridden() ? ($_POST[static::OVERRIDE] ?? $_SERVER[static::OVERRIDE]) : $_SERVER['REQUEST_METHOD'];
        return strtoupper($method);
    }

    /**
     * 是否AJAX请求
     * @return bool
     */
    public static function isAjax(): bool
    {
        if (isset($_SERVER['REQUEST_AJAX']) && $_SERVER['REQUEST_AJAX'] == 1) {
            return true;
        }
        if (self::type() == 'json') {
            return true;
        }
        $requestedWith = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) : '';
        return $requestedWith == 'fetch' || $requestedWith == 'xmlhttprequest';
    }

    /**
     * 是否GET请求
     * @return bool
     */
    public static function isGet(): bool
    {
        return static::method() == 'GET';
    }

    /**
     * 是否POST请求
     * @return bool
     */
    public static function isPost(): bool
    {
        return static::method() == 'POST';
    }

    /**
     * 是否PUT请求
     * @return bool
     */
    public static function isPut(): bool
    {
        return static::method() == 'PUT';
    }

    /**
     * 是否DELETE请求
     * @return bool
     */
    public static function isDelete(): bool
    {
        return static::method() == 'DELETE';
    }


    /**
     * 获取session值
     * @return string
     * @throws CacheException
     */
    public static function getSessionId(): string
    {
        if (USE_REDIS_SESSION) {
            if (empty(!static::$sessionId)) {
                return static::$sessionId;
            }
            $useType = Config::get('session.use_type', 'cookie');
            $keyName = Config::get('session.key_name', 'PHPSESSID');
            if ($useType == 'param') {
                $token = trim(static::param($keyName, ''));
            } elseif ($useType == 'header') {
                $token = trim(static::server('HTTP_' . strtoupper($keyName)));
            } else {
                $token = trim(Request::getCookie($keyName, ''));
            }
            if (empty($token)) {
                do {
                    $token = Util::randWord(30);
                    $has = Redis::exists('sid.' . $token);
                } while ($has);
                //先占用2分钟
                Redis::setex('sid.' . $token, 300, '[]');
            }
            static::setSessionId($token);
            return $token;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return session_id();
    }

    /**
     * 设置sessionId
     * @param string $ssid
     */
    public static function setSessionId(string $ssid): void
    {
        if (USE_REDIS_SESSION) {
            $useType = Config::get('session.use_type', 'cookie');
            if ($useType == 'cookie') {
                $keyName = Config::get('session.key_name', 'PHPSESSID');
                $host = static::host();
                $domain = preg_replace('@^\w+\.@', '', $host);
                $domain = Config::get('session.domain', $domain);
                setcookie($keyName, $ssid, 0, '/', $domain);
            } else if ($useType == 'header') {
                $keyName = Config::get('session.key_name', 'PHPSESSID');
                static::setHeader($keyName, $ssid);
            }
            static::$sessionId = $ssid;
            return;
        }
        session_id($ssid);
    }

    /**
     * 获取 session 相当于 $_SESSION[$name]
     * @param string $name
     * @param null $default
     * @return array|bool|float|int|string|null
     * @throws CacheException
     */
    public static function getSession(string $name = '', $default = null): mixed
    {
        if (USE_REDIS_SESSION) {
            if (static::$sessionData === null) {
                static::$sessionData = [];
                $token = static::getSessionId();
                if (!empty($token)) {
                    $data = Redis::get('sid.' . $token);
                    if (!empty($data) && is_string($data) && Util::isJson($data)) {
                        static::$sessionData = json_decode($data, true);
                        if (!is_array(static::$sessionData)) {
                            static::$sessionData = [];
                        }
                        $timeout = Config::get('session.timeout', 3600);
                        Redis::expire('sid.' . $token, $timeout);
                    }
                }
            }
            return static::lookup(static::$sessionData, $name, $default);
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return static::lookup($_SESSION, $name, $default);
    }

    /**
     * 设置 session
     * @param string|array $name
     * @param mixed $value
     * @throws CacheException
     */
    public static function setSession(string|array $name, mixed $value = null): void
    {
        if (USE_REDIS_SESSION) {
            $token = static::getSessionId();
            if (!empty($token)) {
                $data = static::getSession();
                if (!empty($name)) {
                    if (is_array($name)) {
                        foreach ($name as $k => $v) {
                            $data[$k] = $v;
                        }
                    } else if (is_string($name)) {
                        $data[$name] = $value;
                    }
                    static::$sessionData = $data;
                    $timeout = Config::get('session.timeout', 3600);
                    Redis::setex('sid.' . $token, $timeout, json_encode($data));
                }
            }
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!empty($name)) {
            if (is_array($name)) {
                foreach ($name as $k => $v) {
                    $_SESSION[$k] = $v;
                }
            } else if (is_string($name)) {
                $_SESSION[$name] = $value;
            }
        }
    }

    /**
     * 清空session
     * @throws CacheException
     */
    public static function clearSession(): void
    {
        if (USE_REDIS_SESSION) {
            $token = static::getSessionId();
            if (!empty($token)) {
                Redis::del('sid.' . $token);
            }
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    /**
     * 获取cookie
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public static function getCookie(string $name = '', $default = null): mixed
    {
        return static::lookup($_COOKIE, $name, $default);
    }

    /**
     * 设置cookie
     * @param string $name
     * @param string $value
     * @param array|int|null $options
     * @return bool
     */
    public static function setCookie(string $name, string $value, array|int|null $options = null): bool
    {
        if ($options == null) {
            return setcookie($name, $value);
        }
        if (is_int($options)) {
            return setcookie($name, $value, $options);
        }
        $expire = isset($options['expire']) ? intval($options['expire']) : 0;
        $path = $options['path'] ?? '';
        $domain = $options['domain'] ?? '';
        $secure = isset($options['secure']) && $options['secure'];
        $httponly = isset($options['httponly']) && $options['httponly'];
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     *  获取files 相当于 $_FILE[$name]
     * @param string|null $name
     * @param null $default
     * @return array|null
     */
    public static function files(string $name = null, $default = null): array|null
    {
        if (empty($name)) {
            return $_FILES;
        }
        return $_FILES[$name] ?? $default;
    }

    /**
     * 获取路由
     * @param string $name 支持 ctl:控制器名  act:方法名  app:应用名
     * @param null $default
     * @return ?string
     */
    public static function route(string $name = '', $default = null): ?string
    {
        $route = App::get();
        if (empty($name)) {
            return $route;
        }
        if ($route == null) {
            return $default;
        }
        if (isset($route[$name])) {
            return $route[$name];
        }
        return $default;
    }

    /**
     * 设置请求头
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @param int $http_response_code
     */
    public static function setHeader(string $name, string $value, bool $replace = true, int $http_response_code = 0): void
    {
        $nameTemps = explode('-', $name);
        foreach ($nameTemps as &$n) {
            $n = ucfirst($n);
        }
        $name = join('-', $nameTemps);
        $string = $name . ':' . $value;
        header($string, $replace, $http_response_code);
    }

    /**
     * 设置输出类型
     * @param string $type
     * @param string $encoding
     */
    public static function setContentType(string $type, string $encoding = 'utf-8'): void
    {
        if (!str_contains($type, '/')) {
            $type = static::$formats[$type] ?? 'application/octet-stream';
            if (is_array($type)) {
                $type = $type[0];
            }
        }
        if (in_array($type, static::$formats['json'])) {
            $_SERVER['REQUEST_AJAX'] = 1;
        }
        $contentType = $type . '; charset=' . $encoding;
        self::setHeader('Content-Type', $contentType);
    }

    /**
     * 获取ip
     * @param bool $proxy
     * @param bool $forward
     * @return string
     */
    public static function ip(bool $proxy = false, bool $forward = false): string
    {
        $ip = '';
        $http_agent_ip = Config::get('beacon.http_agent_ip', '');
        if ($http_agent_ip != '') {
            $ip = static::server($http_agent_ip);
        }
        if ($ip == '') {
            if ($proxy) {
                if ($forward) {
                    $forwardIP = static::server('HTTP_X_FORWARDED_FOR');
                    if (!empty($forwardIP)) {
                        $temps = explode(',', $forwardIP);
                        foreach ($temps as $item) {
                            $item = trim($item);
                            if (filter_var($item, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                                return $item;
                            }
                        }
                    }
                } else {
                    $ip = static::server('HTTP_X_REAL_IP');
                }
            } else {
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    $ip = $_SERVER['REMOTE_ADDR'];
                }
            }
        }
        if (empty($ip)) {
            return '127.0.0.1';
        }
        return $ip;
    }


    /**
     * 获取来源页
     * @param string|null $default
     * @return string|null
     */
    public static function referrer(?string $default = ''): string|null
    {
        $referer = static::server('HTTP_REFERER');
        if (empty($referer)) {
            $referer = static::server('HTTP_REFERRER');
        }
        if ($referer && !preg_match('@^(http|https)@i', $referer)) {
            return $default;
        }
        if (empty($referer)) {
            return $default;
        }
        return $referer;
    }


    /**
     * 获取请求字符串
     * @param bool $decorated
     * @return string
     */
    public static function query(bool $decorated = false): string
    {
        if (count((array)$_GET)) {
            $query = http_build_query($_GET);
            return $decorated ? ('?' . $query) : $query;
        }
        return '';
    }


    /**
     * 获取当前请求域名
     * @param bool $decorated
     * @return string
     */
    public static function domain(bool $decorated = false): string
    {
        $host = self::host();
        return $decorated ? sprintf("%s://%s", self::scheme(), $host) : $host;
    }
}
