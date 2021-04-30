<?php


namespace beacon\core;

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

class View extends Sdopx
{
    public function __construct()
    {
        parent::__construct();
        $config = Config::get('sdopx.*');
        //模板目录
        $templateDir = empty($config['template_dir']) ? 'view' : $config['template_dir'];
        if (is_array($templateDir)) {
            foreach ($templateDir as &$dir) {
                $dir = Util::trimPath($dir);
                $dir = Util::path(ROOT_DIR, $dir);
            }
        } else {
            $templateDir = Util::trimPath($templateDir);
            $templateDir = Util::path(ROOT_DIR, $templateDir);
        }
        //公共模板目录
        $commonDir = Util::trimPath(empty($config['common_dir']) ? 'view/common' : $config['common_dir']);
        $commonDir = Util::path(ROOT_DIR, $commonDir);
        //运行时目录
        $runtimeDir = Util::trimPath(empty($config['runtime_dir']) ? 'runtime' : $config['runtime_dir']);
        $runtimeDir = Util::path(ROOT_DIR, $runtimeDir);
        if (!is_dir($runtimeDir)) {
            Util::makeDir($runtimeDir);
        }
        $this->setTemplateDir($templateDir);
        $this->addTemplateDir($commonDir, 'common');
        $this->setCompileDir($runtimeDir);
        //设置边界符号
        if (!empty($config['left_delimiter']) && !empty($config['right_delimiter'])) {
            $this->leftDelimiter = $config['left_delimiter'];
            $this->rightDelimiter = $config['right_delimiter'];
        }
        //强行编译
        if (isset($config['compile_force'])) {
            $this->compileForce = $config['compile_force'];
        }
        //检查编译
        if (isset($config['compile_check'])) {
            $this->compileCheck = $config['compile_check'];
        }
    }

    /**
     * 设置模板引擎上下文
     * @param Controller $controller
     */
    public function context(Controller $controller)
    {
        $this->context = $controller;
        $this->assign('this', $controller);
    }
}