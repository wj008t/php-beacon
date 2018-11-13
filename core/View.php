<?php

namespace beacon;

use sdopx\Sdopx;

//开启调试模式
if (Config::get('sdopx.debug')) {
    Sdopx::$debug = true;
}

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/12
 * Time: 15:50
 */
class View implements ViewInterface
{

    private static $instance = null;
    /**
     * @var Sdopx
     */
    public $template = null;

    /**
     * @return View|Sdopx
     */
    public static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new View();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $config = Config::get('sdopx.*');
        $this->template = $sdopx = new Sdopx();
        //模板目录
        $templateDir = empty($config['template_dir']) ? 'view' : $config['template_dir'];
        if (is_array($templateDir)) {
            foreach ($templateDir as &$dir) {
                $dir = Utils::path(ROOT_DIR, $dir);
            }
        } else {
            $templateDir = Utils::path(ROOT_DIR, $templateDir);
        }
        //公共模板目录
        $commonDir = Utils::path(ROOT_DIR, empty($config['common_dir']) ? 'view/common' : $config['common_dir']);
        //运行时目录
        $runtimeDir = Utils::path(ROOT_DIR, empty($config['runtime_dir']) ? 'runtime' : $config['runtime_dir']);
        $sdopx->setTemplateDir($templateDir);
        $sdopx->addTemplateDir($commonDir, 'common');
        $sdopx->setCompileDir($runtimeDir);
        //设置边界符号
        if (!empty($config['leftDelimiter']) && !empty($config['rightDelimiter'])) {
            $sdopx->leftDelimiter = $config['leftDelimiter'];
            $sdopx->rightDelimiter = $config['rightDelimiter'];
        }
        //强行编译
        if (isset($config['compileForce'])) {
            $sdopx->compileForce = $config['compileForce'];
        }
        //检查编译
        if (isset($config['compileCheck'])) {
            $sdopx->compileForce = $config['compileCheck'];
        }
    }

    public function assign($key, $val = null)
    {
        $this->template->assign($key, $val);
    }

    public function fetch(string $tplName)
    {
        $this->template->fetch($tplName);
    }

    public function display(string $tplName)
    {
        $this->template->display($tplName);
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->template, $method], $params);
    }

    /**
     * 设置模板引擎上下文
     * @param Controller $controller
     */
    public function context(Controller $controller)
    {
        $this->template->context = $controller;
        $this->assign('this', $controller);
    }
}