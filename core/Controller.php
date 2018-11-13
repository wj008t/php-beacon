<?php

namespace beacon;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/11
 * Time: 20:40
 */
abstract class Controller
{

    /**
     * 获取视图
     * @return View|\sdopx\Sdopx
     */
    protected function view()
    {
        return View::instance();
    }

    /**
     * 注册变量
     * @param $key
     * @param null $val
     */
    protected function assign($key, $val = null)
    {
        $this->view()->assign($key, $val);
    }

    /**
     * 获取已注册参数
     * @return array|mixed|null
     */
    protected function getAssign()
    {
        return $this->view()->getAssign();
    }

    /**
     * 输出显示
     * @param string $tplName
     * @param string|null $parent
     */
    protected function display(string $tplName, string $parent = null)
    {
        $this->view()->context($this);
        $this->setContentType('html');
        if ($parent !== null) {
            return $this->view()->display($this, 'extends:' . $parent . '|' . $tplName);
        }
        return $this->view()->display($this, $tplName);
    }

    /**
     * 获取内容
     * @param string $tplname
     * @param string|null $parent
     * @return mixed|string|void
     */
    protected function fetch(string $tplname, string $parent = null)
    {
        $this->view()->context($this);
        if ($parent !== null) {
            return $this->view()->fetch($this, 'extends:' . $parent . '|' . $tplname);
        }
        return $this->view()->fetch($this, $tplname);
    }

    /**
     * 设置跳转
     * @param string $url
     * @param array $query
     */
    protected function redirect(string $url, array $query = [])
    {
        $url = empty($url) ? '/' : $url;
        $url = Route::url($url, $query);
        Request::instance()->setHeader('Location', $url);
        exit;
    }

    /**
     * @param $error string|array
     * @param $option array :data code back template 等
     * 如 ['data'=>$mydata,'back'=>'/index','code'=>33,'template'=>'myerror.tpl']
     */
    public function error($error, array $option = [])
    {
        $option['status'] = false;
        if (is_array($error)) {
            $option['formError'] = $error;
            reset($error);
            $option['error'] = current($error);
            $option['error'] = $option['error'] == null ? '错误' : $option['error'];
        } else {
            $option['error'] = $error;
        }
        if ($this->getContentType() == 'application/json' || $this->getContentType() == 'text/json') {
            echo json_encode($option, JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            if (empty($option['back'])) {
                $option['back'] = $this->getReferrer();
            }
            $this->assign('info', $option);
            if (!empty($option['template'])) {
                $this->display($option['template']);
            } else {
                $template = Config::get('template_error', '@error.tpl');
                $this->display($template);
            }
            exit;
        }
    }

    /**
     * 显示正确信息
     * @param null $message
     * @param array $option :data code back template 等
     * 如 ['data'=>$mydata,'back'=>'/index','code'=>33,'template'=>'myerror.tpl']
     */
    public function success($message = null, array $option = [])
    {
        $option = [];
        $option['status'] = true;
        if (!empty($message)) {
            $option['message'] = $message;
        }
        if ($this->getContentType() == 'application/json' || $this->getContentType() == 'text/json') {
            echo json_encode($option, JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            if (empty($option['back'])) {
                $back = $this->param('__BACK__');
                if (empty($back)) {
                    $back = $this->getReferrer();
                }
                $option['back'] = $back;
            }
            $this->assign('info', $option);
            if (!empty($option['template'])) {
                $this->display($option['template']);
            } else {
                $template = Config::get('template_success', '@success.tpl');
                $this->display($template);
            }
            exit;
        }
    }

    /**
     * get 获取数据 相当于 $_GET
     * @param string|null $name 键名 也可以指定 返回类型 如  get('name:s')  get('age:i')  支持 a:array  i:int  f:float b:bool
     * @param null $def 默认值
     * @return array|mixed|null
     */
    public function get(string $name = null, $def = null)
    {
        return Request::instance()->get($name, $def);
    }

    /**
     * post 获取数据 相当于 $_POST
     * @param string|null $name 键名 也可以指定 返回类型 如  post('name:s')  post('age:i')  支持 a:array  i:int  f:float b:bool
     * @param null $def 默认值
     * @return array|mixed|null
     */
    public function post(string $name = null, $def = null)
    {
        return Request::instance()->post($name, $def);
    }

    /**
     * param 获取数据 相当于 $_REQUEST
     * @param string|null $name
     * @param null $def
     * @return array|mixed|null
     */
    public function param(string $name = null, $def = null)
    {
        return Request::instance()->param($name, $def);
    }

    /**
     * 获取 session 相当于 $_SESSION[$name]
     * @param string|null $name
     * @param null $def
     * @return null
     */
    public function getSession(string $name = null, $def = null)
    {
        return Request::instance()->getSession($name, $def);
    }

    /**
     * 设置 session
     * @param string $name
     * @param $value
     */
    protected function setSession(string $name, $value)
    {
        return Request::instance()->setSession($name, $value);
    }

    /**
     * 清空session
     */
    protected function delSession()
    {
        return Request::instance()->delSession();
    }

    /**
     * 获取cookie
     * @param string $name
     * @param null $def
     * @return null
     */
    public function getCookie(string $name, $def = null)
    {
        return Request::instance()->getCookie($name, $def);
    }

    /**
     * 设置cookie
     * @param string $name
     * @param $value
     * @param $options
     * @return bool
     */
    protected function setCookie(string $name, $value, $options)
    {
        return Request::instance()->setCookie($name, $value, $options);
    }

    /**
     * 获取文件
     * @param string|null $name
     * @return null
     */
    protected function file(string $name = null)
    {
        return Request::instance()->file($name);
    }

    /**
     * 获取路由
     * @param string|null $name 支持 ctl:控制器名  act:方法名  app:应用名
     * @param null $def
     * @return null
     */
    public function route(string $name = null, $def = null)
    {
        return Request::instance()->route($name, $def);
    }

    /**
     * 获取请求头
     * @param string|null $name
     * @return array|mixed|null|string
     */
    public function getHeader(string $name = null)
    {
        return Request::instance()->getHeader($name);
    }

    /**
     * 设置请求头
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @param null $http_response_code
     */
    protected function setHeader(string $name, string $value, bool $replace = true, $http_response_code = null)
    {
        return Request::instance()->setHeader($name, $value, $replace, $http_response_code);
    }

    /**
     * 获取IP
     * @param bool $proxy
     * @param bool $forward
     * @return array|mixed|null|string
     */
    protected function getIP(bool $proxy = false, bool $forward = false)
    {
        return Request::instance()->getIP($proxy, $forward);
    }

    /**
     * 获取 内容类型
     * @param bool $whole
     * @return array|mixed|null|string
     */
    protected function getContentType(bool $whole = false)
    {
        return Request::instance()->getContentType($whole);
    }

    /**
     * 设置内容类型
     * @param string $type
     * @param string $encoding
     */
    protected function setContentType(string $type, string $encoding = 'utf-8')
    {
        return Request::instance()->setContentType($type, $encoding);
    }

    /**
     * 获取配置项
     * @param string $name
     * @param null $def
     * @return mixed|string
     */
    protected function config(string $name, $def = null)
    {
        return Request::instance()->config($name, $def);
    }

    /**
     * 根据模板补丁修正输出数据
     * @param string $tplName
     * @param array|null $list
     * @param array $orgFields
     * @param string $assign
     * @return array
     */
    protected function hook(string $tplName, array $list = null, array $orgFields = [], string $assign = 'rs')
    {
        if ($list == null) {
            $data = $this->getAssign();
            $list = (isset($data['list']) && is_array($data['list'])) ? $data['list'] : [];
        }
        if (!isset($list[0])) {
            return $list;
        }
        $this->fetch($tplName);
        $retList = [];
        $view = $this->view();
        $hookFunc = $view->getHook();
        foreach ($list as $item) {
            $column = [];
            foreach ($orgFields as $key) {
                if (isset($item[$key])) {
                    $column[$key] = $item;
                }
            }
            foreach ($hookFunc as $key => $func) {
                $column[$key] = call_user_func($func, [$assign => $item]);
            }
            $retList[] = $column;
        }
        return $retList;
    }

    /**
     * 是否get请求
     * @return bool
     */
    protected function isGet()
    {
        return Request::instance()->isGet();
    }

    /**
     * 判断请求方式
     * @param string $method
     * @return bool
     */
    protected function isMethod(string $method)
    {
        return Request::instance()->isMethod($method);
    }

    /**
     * 获取请求方式
     * @return string
     */
    public function getMethod()
    {
        return Request::instance()->getMethod();
    }

    /**
     * 是否post 请求
     * @return bool
     */
    public function isPost()
    {
        return Request::instance()->isPost();
    }

    /**
     * 是否ajax
     * @return bool
     */
    public function isAjax()
    {
        return Request::instance()->isAjax();
    }

    /**
     * 获取来源页
     * @return array|mixed|null|string
     */
    public function getReferrer()
    {
        return Request::instance()->getReferrer();
    }
}
