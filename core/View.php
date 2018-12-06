<?php

namespace beacon;

use sdopx\Sdopx;

//开启调试模式
if (Config::get('sdopx.debug')) {
    Sdopx::$debug = true;
}
//设置后缀
if (Config::get('sdopx.extension')) {
    Sdopx::$extension = Config::get('sdopx.extension');
}
//注册配置项
Sdopx::registerConfig(function ($key) {
    return Config::get($key);
});


/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/12
 * Time: 15:50
 */
class View
{

    /**
     * @var View|Sdopx
     */
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
        $this->template = $sdopx = new Sdopx();
        //如果已经有实例了，再次创建的时候使用相同的设置,可能会在实例中设置
        if (self::$instance) {
            $template = self::$instance->template;
            $sdopx->setCompileDir($template->compileDir);
            $sdopx->setTemplateDir($template->getTemplateDir());
            $sdopx->leftDelimiter = $template->leftDelimiter;
            $sdopx->rightDelimiter = $template->rightDelimiter;
            $sdopx->compileForce = $template->compileForce;
            $sdopx->compileCheck = $template->compileCheck;
            $sdopx->context = $template->context;
            $this->assign('this', $sdopx->context);
        } else {
            $config = Config::get('sdopx.*');
            //模板目录
            $templateDir = empty($config['template_dir']) ? 'view' : $config['template_dir'];
            if (is_array($templateDir)) {
                foreach ($templateDir as &$dir) {
                    $dir = Utils::trimPath($dir);
                    $dir = Utils::path(ROOT_DIR, $dir);
                }
            } else {
                $templateDir = Utils::trimPath($templateDir);
                $templateDir = Utils::path(ROOT_DIR, $templateDir);
            }
            //公共模板目录
            $commonDir = Utils::trimPath(empty($config['common_dir']) ? 'view/common' : $config['common_dir']);
            $commonDir = Utils::path(ROOT_DIR, $commonDir);
            //运行时目录
            $runtimeDir = Utils::trimPath(empty($config['runtime_dir']) ? 'runtime' : $config['runtime_dir']);
            $runtimeDir = Utils::path(ROOT_DIR, $runtimeDir);
            $sdopx->setTemplateDir($templateDir);
            $sdopx->addTemplateDir($commonDir, 'common');
            $sdopx->setCompileDir($runtimeDir);


            //设置边界符号
            if (!empty($config['left_delimiter']) && !empty($config['right_delimiter'])) {
                $sdopx->leftDelimiter = $config['left_delimiter'];
                $sdopx->rightDelimiter = $config['right_delimiter'];
            }
            //强行编译
            if (isset($config['compile_force'])) {
                $sdopx->compileForce = $config['compile_force'];
            }
            //检查编译
            if (isset($config['compile_check'])) {
                $sdopx->compileCheck = $config['compile_check'];
            }
        }
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