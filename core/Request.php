<?php

namespace beacon;
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/11
 * Time: 20:44
 */
class Request
{

    private static $header = null;

    /**
     * 数据输入格式化
     * @param array $data
     * @param string|null $name
     * @param null $def
     * @return array|bool|float|mixed|null|string
     */
    public static function input(array $data, string $name = null, $def = null)
    {
        if (empty($name)) {
            return $data;
        }
        $type = '';
        if (preg_match('@^(.*):([abfis])@', $name, $m)) {
            $type = $m[2];
            $name = $m[1];
        }
        switch ($type) {
            case 's':
                if (!isset($data[$name])) {
                    if (is_string($def)) {
                        return $def;
                    }
                    return '';
                }
                if (is_string($data[$name])) {
                    return $data[$name];
                }
                return strval($data[$name]);
            case 'b':
                if (!isset($data[$name])) {
                    if (is_bool($def)) {
                        return $def;
                    }
                    if (is_string($def)) {
                        return $def === '1' || $def === 'on' || $def === 'yes' || $def === 'true';
                    }
                    return false;
                }
                return $data[$name] === '1' || $data[$name] === 'on' || $data[$name] === 'yes' || $data[$name] === 'true';
            case 'f':
                if (!isset($data[$name]) || !is_numeric($data[$name])) {
                    if (is_double($def) || is_float($def)) {
                        return $def;
                    }
                    return 0;
                }
                return doubleval($data[$name]);
            case 'i':
                if (!isset($data[$name]) || !is_numeric($data[$name])) {
                    if (is_integer($def)) {
                        return $def;
                    }
                    return 0;
                }
                return intval($data[$name]);
            case 'a':

                if (!isset($data[$name])) {
                    if (is_array($def)) {
                        return $def;
                    }
                    if ($def === null || $def === '') {
                        return [];
                    }
                    return [$def];
                }
                if (is_array($data[$name])) {
                    return $data[$name];
                }
                if (is_string($data[$name]) && Utils::isJson($data[$name])) {
                    return json_decode($data[$name], true);
                }
                //拆分，取值
                if (is_string($data[$name]) && preg_match('@^\d+(,\d+)*$@', $data[$name])) {
                    $retemp = [];
                    $temp = explode(',', $data[$name]);
                    foreach ($temp as $item) {
                        if (is_numeric($item)) {
                            $retemp[] = intval($item);
                        }
                    }
                    return $retemp;
                }
                if ($data[$name] === null || $data[$name] === '') {
                    return [];
                }
                return [$data[$name]];
            default:
                return isset($data[$name]) ? $data[$name] : $def;
        }
    }

    /**
     * get 获取数据 相当于 $_GET
     * @param string|null $name
     * @param null $def
     * @return array|bool|float|mixed|null|string
     */
    public static function get(string $name = null, $def = null)
    {
        return self::input($_GET, $name, $def);
    }

    /**
     * post 获取数据 相当于 $_POST
     * @param string|null $name
     * @param null $def
     * @return array|bool|float|mixed|null|string
     */
    public static function post(string $name = null, $def = null)
    {
        return self::input($_POST, $name, $def);
    }

    /**
     * param 获取数据 相当于 $_REQUEST
     * @param string|null $name
     * @param null $def
     * @return array|bool|float|mixed|null|string
     */
    public static function param(string $name = null, $def = null)
    {
        return self::input($_REQUEST, $name, $def);
    }

    /**
     * 获取 session 相当于 $_SESSION[$name]
     * @param string|null $name
     * @param null $def
     * @return null
     */
    public static function getSession(string $name = null, $def = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($name)) {
            return $_SESSION;
        }
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $def;
    }

    /**
     * 设置 session
     * @param string $name
     * @param $value
     */
    public static function setSession(string $name, $value)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[$name] = $value;
    }

    /**
     * 清空session
     */
    public static function delSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    /**
     * 获取cookie
     * @param string $name
     * @param null $def
     * @return null
     */
    public static function getCookie(string $name, $def = null)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $def;
    }

    /**
     * 设置cookie
     * @param string $name
     * @param $value
     * @param $options
     * @return bool
     */
    public static function setCookie(string $name, $value, $options)
    {
        if ($options == null) {
            return setcookie($name, $value);
        }
        if (is_integer($options)) {
            return setcookie($name, $value, $options);
        }
        $expire = isset($options['expire']) ? intval($options['expire']) : 0;
        $path = isset($options['path']) ? intval($options['path']) : '';
        $domain = isset($options['domain']) ? intval($options['domain']) : '';
        $secure = isset($options['secure']) ? intval($options['secure']) : false;
        $httponly = isset($options['httponly ']) ? intval($options['httponly ']) : false;
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * 获取file 相当于 $_FILE[$name]
     * @param string|null $name
     * @return null
     */
    public static function file(string $name = null)
    {
        if (empty($name)) {
            return $_FILES;
        }
        return isset($_FILES[$name]) ? $_FILES[$name] : null;
    }

    /**
     * 获取路由
     * @param string|null $name 支持 ctl:控制器名  act:方法名  app:应用名
     * @param null $def
     * @return null
     */
    public static function route(string $name = null, $def = null)
    {
        $route = Route::get();
        if (empty($name)) {
            return $route;
        }
        if ($route == null) {
            return $def;
        }
        if (isset($route[$name])) {
            return $route[$name];
        }
        return $def;
    }

    /**
     * 获取请求头
     * @param string|null $name
     * @return array|mixed|null|string
     */
    public static function getHeader(string $name = null)
    {
        if (self::$header == null) {
            self::$header = [];
            foreach ($_SERVER as $key => $value) {
                if ('HTTP_' == substr($key, 0, 5)) {
                    $key = strtolower(str_replace('_', '-', substr($key, 5)));
                    self::$header[$key] = $value;
                }
            }
        }
        $list = headers_list();
        foreach ($list as $item) {
            if (preg_match('@^([^:]+):(.*)$@', $item, $mt)) {
                $key = trim($mt[1]);
                $value = trim($mt[2]);
                $key = strtolower(str_replace('_', '-', $key));
                self::$header[$key] = $value;
            }
        }
        if (empty($name)) {
            return self::$header;
        }
        $name = strtolower(str_replace('_', '-', $name));
        return isset(self::$header[$name]) ? self::$header[$name] : '';
    }

    /**
     * 设置请求头
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @param null $http_response_code
     */
    public static function setHeader(string $name, string $value, bool $replace = true, $http_response_code = null)
    {
        $nameTemps = explode('-', $name);
        foreach ($nameTemps as &$n) {
            $n = ucfirst($n);
        }
        $name = join('-', $nameTemps);
        $string = $name . ':' . $value;
        if ($replace) {
            if ($http_response_code == null) {
                @header($string);
            } else {
                @header($string, $replace, $http_response_code);
            }
        } else {
            if ($http_response_code == null) {
                @header($string, false);
            } else {
                @header($string, false, $http_response_code);
            }
        }
    }

    /**
     * 获取ip
     * @param bool $proxy
     * @param bool $forward
     * @return array|mixed|null|string
     */
    public static function getIP(bool $proxy = false, bool $forward = false)
    {
        $ip = '';
        if ($proxy) {
            if ($forward) {
                $forwardIP = self::getHeader('x-forwarded-for');
                if (!empty($forwardIP)) {
                    $temps = explode(',', $forwardIP);
                    foreach ($temps as $item) {
                        $item = trim($item);
                        if (filter_var($item, FILTER_VALIDATE_IP)) {
                            return $item;
                        }
                    }
                }
                $ip = self::getHeader('x-real-ip');
            }
        } else {
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }
        if (empty($ip)) {
            return '127.0.0.1';
        }
        return $ip;
    }

    /**
     * 获取 内容类型
     * @param bool $whole
     * @return array|mixed|null|string
     */
    public static function getContentType($whole = false)
    {
        $content_type = self::getHeader('content-type');
        if ($whole) {
            return $content_type;
        }
        $temp = explode(';', $content_type);
        return $temp[0];
    }

    /**
     * 设置内容类型
     * @param $type
     * @param string $encoding
     */
    public static function setContentType($type, $encoding = 'utf-8')
    {
        if (strpos($type, '/') === false) {
            $mime_types = [
                'txt' => 'text/plain',
                'htm' => 'text/html',
                'html' => 'text/html',
                'php' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'text/json',
                'xml' => 'application/xml',
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
            $type = isset($mime_types[$type]) ? $mime_types[$type] : 'application/octet-stream';
        }
        $contentType = $type . '; charset=' . $encoding;
        self::setHeader('Content-Type', $contentType);
    }

    /**
     * 获取配置项
     * @param string $name
     * @param null $def
     * @return mixed|string
     */
    public static function config(string $name, $def = null)
    {
        return Config::get($name, $def);
    }

    /**
     * 是否get请求
     * @return bool
     */
    public static function isGet()
    {
        return self::isMethod('get');
    }

    /**
     * 判断请求方式
     * @param string $method
     * @return bool
     */
    public static function isMethod(string $method)
    {
        return strtolower($_SERVER['REQUEST_METHOD']) == strtolower($method) ? true : false;
    }

    /**
     * 获取请求方式
     * @return string
     */
    public static function getMethod()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * 是否post 请求
     * @return bool
     */
    public static function isPost()
    {
        return self::isMethod('post');
    }

    /**
     * 是否ajax
     * @return bool
     */
    public static function isAjax()
    {
        if (isset($_SERVER['REQUEST_AJAX']) && $_SERVER['REQUEST_AJAX'] == true) {
            return true;
        }
        return strtolower(self::getHeader('x-requested-with')) === 'xmlhttprequest';
    }

    /**
     * 获取来源页
     * @return array|mixed|null|string
     */
    public static function getReferrer()
    {
        $referer = self::getHeader('referer');
        if (empty($referer)) {
            $referer = self::getHeader('referrer');
        }
        return $referer;
    }
}
