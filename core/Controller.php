<?php


namespace beacon\core;


abstract class Controller
{
    protected ?View $_view = null;

    protected function view(): View
    {
        if ($this->_view == null) {
            $this->_view = new View();
        }
        return $this->_view;
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
     * @return array
     */
    protected function getAssign(): array
    {
        $data = $this->view()->getAssign();
        unset($data['this']);
        return $data;
    }

    /**
     * 输出显示
     * @param string $tplName
     * @param string|null $parent
     */
    protected function display(string $tplName, ?string $parent = null)
    {
        $this->view()->context($this);
        Request::setContentType('html');
        if ($parent !== null) {
            $this->view()->display('extends:' . $parent . '|' . $tplName);
            return;
        }
        $this->view()->display($tplName);
    }

    /**
     * 获取内容
     * @param string $tplname
     * @param string|null $parent
     * @return string
     */
    protected function fetch(string $tplname, ?string $parent = null): string
    {
        $this->view()->context($this);
        if ($parent !== null) {
            return $this->view()->fetch('extends:' . $parent . '|' . $tplname);
        }
        return $this->view()->fetch($tplname);
    }

    /**
     * @param string $url
     * @param array $query
     */
    protected function redirect(string $url, array $query = [])
    {
        $url = empty($url) ? '/' : $url;
        $url = App::url($url, $query);
        Request::setHeader('Location', $url);
        exit;
    }

    /**
     * 输出错误
     * @param $error string|array
     * @param $option ?array :data code back template 等
     * 如 ['data'=>$myData,'back'=>'/index','code'=>33,'template'=>'myError.tpl']
     */
    protected function error(string|array $error, ?array $option = [])
    {
        $option['status'] = false;
        if (is_array($error)) {
            $option['formError'] = $error;
            reset($error);
            $option['msg'] = current($error);
            $option['msg'] = $option['msg'] == null ? '错误' : $option['msg'];
        } else {
            $option['msg'] = $error;
        }
        if (Request::isAjax()) {
            Request::setContentType('json');
            echo json_encode($option, JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (empty($option['back'])) {
            $option['back'] = Request::referrer();
        }
        $this->assign('info', $option);
        if (!empty($option['template'])) {
            $this->display($option['template']);
        } else {
            $template = Config::get('beacon.error_template', '@error.tpl');
            $this->display($template);
        }
        exit;
    }


    /**
     * 显示正确信息
     * @param null $message
     * @param array $option :data code back template 等
     * 如 ['data'=>$myData,'back'=>'/index','code'=>33,'template'=>'myError.tpl']
     */
    protected function success($message = null, array $option = [])
    {
        $option['status'] = true;
        if (!empty($message)) {
            $option['msg'] = $message;
        }
        if (Request::isAjax()) {
            Request::setContentType('json');
            echo json_encode($option, JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (empty($option['back'])) {
            $back = $this->param('__BACK__');
            if (empty($back)) {
                $back = Request::referrer();
            }
            $option['back'] = $back;
        }
        $this->assign('info', $option);
        if (!empty($option['template'])) {
            $this->display($option['template']);
        } else {
            $template = Config::get('beacon.success_template', '@success.tpl');
            $this->display($template);
        }
        exit;
    }


    /**
     * @param string $name
     * @param null $def
     * @return array|bool|float|int|string|null
     */
    public function get(string $name = '', $def = null): array|bool|float|int|string|null
    {
        return Request::get($name, $def);
    }

    /**
     * post 获取数据 相当于 $_POST
     * @param string $name
     * @param null $def
     * @return array|bool|float|int|string|null
     */
    public function post(string $name = '', $def = null): array|bool|float|int|string|null
    {
        return Request::post($name, $def);
    }

    /**
     * param 获取数据 相当于 $_REQUEST
     * @param string $name
     * @param null $def
     * @return array|bool|float|int|string|null
     */
    public function param(string $name = '', $def = null): array|bool|float|int|string|null
    {
        return Request::param($name, $def);
    }

    /**
     * 获取路由
     * @param string $name 支持 ctl:控制器名  act:方法名  app:应用名
     * @param null $def
     * @return array|bool|float|int|string|null
     */
    public function route(string $name = '', $def = null): array|bool|float|int|string|null
    {
        return Request::route($name, $def);
    }

    /**
     * 是否AJAX请求
     * @return bool
     */
    protected function isAjax(): bool
    {
        return Request::isAjax();
    }

    /**
     * 是否GET请求
     * @return bool
     */
    protected function isGet(): bool
    {
        return Request::isGet();
    }

    /**
     * 是否POST请求
     * @return bool
     */
    protected function isPost(): bool
    {
        return Request::isPost();
    }

    /**
     * 获取来源链接
     * @return string
     */
    public function referrer(): string
    {
        return Request::referrer();
    }


    /**
     * 显示表单
     * @param Form $form
     * @param string $template
     */
    protected function displayForm(Form $form, string $template = '')
    {
        $this->assign('form', $form);
        if (empty($template)) {
            if (!empty($form->template)) {
                $template = $form->template;
            }
        }
        return $this->display($template);
    }

    /**
     * 完成表单
     * @param Form $form
     * @return array
     */
    protected function completeForm(Form $form): array
    {
        $form->autoComplete();
        if (!$form->validate($error)) {
            $this->error($error);
        }
        return $form->getData();
    }


    /**
     * 使用模板填充数据
     * @param array $list
     * @param string $template
     * @return array
     */
    protected function hookData(array $list, string $template = ''): array
    {
        $view = new View();
        $view->context($this);
        $view->fetch($template);
        $hook = $view->getHook();
        $temp = [];
        foreach ($list as $rs) {
            $item = [];
            foreach ($hook as $key => $func) {
                $item[$key] = call_user_func($func, ['rs' => $rs]);
            }
            foreach ($rs as $key => $value) {
                if (str_starts_with($key, '_')) {
                    $item[$key] = $value;
                }
            }
            $temp[] = $item;
        }
        return $temp;
    }
}