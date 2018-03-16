<?php

namespace beacon;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/12
 * Time: 15:50
 */

class ViewEngine
{
    public function get($key, $def = null)
    {
        return Request::instance()->get($key, $def);
    }

    public function post($key, $def = null)
    {
        return Request::instance()->post($key, $def);
    }

    public function param($key, $def = null)
    {
        return Request::instance()->param($key, $def);
    }

    public function route($name)
    {
        return Route::get($name);
    }
}

class View
{

    private $_book = [];
    private $_config_vars = [];

    /**
     * @var \sdopx\Sdopx
     */
    public $engine = null;

    private static $instance = null;

    public static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new View();
        }
        return self::$instance;
    }

    public function assign($key, $val = null)
    {
        if (is_array($key)) {
            $this->_book = array_replace($this->_book, $key);
        } else {
            $this->_book[$key] = $val;
        }
    }

    public function getAssign()
    {
        return $this->_book;
    }

    public function assignConfig($key, $val = null)
    {
        if (is_array($key)) {
            $this->_config_vars = array_replace($this->_config_vars, $key);
        } else {
            $this->_config_vars[$key] = $val;
        }
    }

    public static function newInstance()
    {
        if (Config::get('sdopx.debug')) {
            \sdopx\Sdopx::$debug = true;
        }
        $engine = new \sdopx\Sdopx();
        $template_dir = Config::get('sdopx.template_dir', 'view');
        if (is_array($template_dir)) {
            foreach ($template_dir as &$dir) {
                $dir = Utils::path(ROOT_DIR, $dir);
            }
        } else {
            $template_dir = Utils::path(ROOT_DIR, $template_dir);
        }
        $common_dir = Utils::path(ROOT_DIR, Config::get('sdopx.common_dir', 'view/common'));
        $runtime_dir = Utils::path(ROOT_DIR, Config::get('sdopx.runtime_dir', 'runtime'));
        $engine->setTemplateDir($template_dir);
        $engine->addTemplateDir($common_dir, 'common');
        $engine->setRuntimeDir($runtime_dir);
        $plugins_dir = Config::get('sdopx.plugin_dir');
        if (!empty($plugins_dir)) {
            if (is_array($plugins_dir)) {
                foreach ($plugins_dir as &$item) {
                    $item = Utils::path(ROOT_DIR, $item);
                }
            } elseif (is_string($plugins_dir)) {
                $plugins_dir = Utils::path(ROOT_DIR, $plugins_dir);
            }
            $engine->addPluginDir($plugins_dir);
        }
        foreach ([
                     'compile_force',
                     'compile_check',
                     'runtime_dir',
                     'left_delimiter',
                     'right_delimiter'
                 ] as $key) {
            $val = Config::get('sdopx.' . $key);
            if (!empty($val)) {
                $engine->setting($key, $val);
            }
        }
        return $engine;
    }

    public function initialize()
    {
        if ($this->engine != null) {
            return;
        }
        $this->engine = self::newInstance();
    }

    public function display(Controller $ctl, $tplname)
    {
        $this->initialize();
        $this->engine->_book = $this->_book;
        $this->engine->_book['this'] = $ctl;
        $this->engine->_config = Config::get();
        echo $this->engine->fetch($tplname);
    }

    public function fetch(Controller $ctl, $tplname)
    {
        $this->initialize();
        $this->engine->_book = $this->_book;
        $this->engine->_book['this'] = $ctl;
        $this->engine->_config = Config::get();
        return $this->engine->fetch($tplname);
    }

    public function hackData(Controller $ctl, $tplname, array $items)
    {
        if (!isset($items[0])) {
            return $items;
        }
        $this->initialize();
        $engine = $this->engine;
        $engine->fetch($tplname);
        $temp = [];
        $hackFuncs = $engine->getHack();
        $engine->_book = array_replace([], $this->_book);
        $engine->_book['this'] = $ctl;
        $engine->_config = Config::get();
        foreach ($items as $item) {
            $engine->_book['rs'] = $item;
            $column = [];
            foreach ($hackFuncs as $key => $func) {
                $column[] = $func();
            }
            $temp[] = $column;
        }
        return $temp;
    }

}