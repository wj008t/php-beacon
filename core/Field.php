<?php

namespace beacon;

use beacon\widget\WidgetInterface;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 14:05
 */


/**
 * 字段类
 * @property $value
 * @property $boxName string
 * @property $boxId string
 *
 * @property $dataValRule array  验证规则
 * @property $dataValMessage array  验证提示
 * @property $dataValDefault string  验证默认信息
 * @property $dataValCorrect string  验证正确信息
 * @property $dataValError string  验证初始返回错误信息
 * @property $dataValDisabled bool  关闭验证
 * @property $dataValOutput string 输出html节点jq选择
 * @property $header
 * //容器中用到的字段
 * @property $plugName string 插件类名
 * @property $mode string 模式
 * @property $skin int 皮肤索引
 * @property $skinName string 皮肤
 * @property $childError array 子元素错误
 *
 * @property $names array 多个名称
 * @property $itemType string 子项数据类型
 * @property $hideBox bool  隐藏输入
 * @property $autoSave bool
 * @property $dataDynamic array
 * @property $encodeValue string 密码框需要的字段
 * @property $viewHide bool 隐藏行
 */
class Field
{

    private static $instance = [];
    /**
     * 所在表单
     * @var Form|null
     */
    private $form = null;

    /**
     * 输入框属性
     * @var array
     */
    private $_attr = [
        'id' => '',
        'name' => ''
    ];
    /**
     * 绑定数据
     * @var array
     */
    private $_data = [];

    /**
     * 视图数据
     * @var array
     */
    private $_view = [];

    /**
     * 扩展函数
     * @var array
     */
    private $_func = [];

    /**
     * 字段属性扩展
     * @var array
     */
    private $_extends = [];

    //-----------------------------------------------------------
    private $_value = null;         //值
    public $default = null;         //默认值
    public $forceDefault = false;   //如果值为空强制默认值
    public $label = '';             //标题
    public $name = '';              //字段名
    public $error = null;           //错误信息
    public $close = false;          //关闭控件
    public $offEdit = false;        //禁止编辑
    public $offSave = false;        //禁止保存
    public $type = 'text';          //字段类型
    public $varType = 'string';     //值类型
    public $dynamic = null;         //动态控制数据
    //视图属性
    public $tabIndex = '';          //所在标签名称,如果为空,所有标签都会出现
    public $viewClose = false;      //关闭视图
    public $viewMerge = 0;      //关闭视图

    /**
     * @var Field
     */
    public $next = null;
    /**
     * @var Field
     */
    public $prev = null;
    //----------------------------------------------------------------
    private $_ref = null;

    public function __construct(Form $form = null, array $field = [])
    {
        $this->_ref = new \ReflectionClass(get_class($this));
        $this->form = $form;
        if ($field == null) {
            $field = [];
        }
        foreach ($field as $key => $value) {
            $key = Utils::attrToCamel($key);
            if (empty($key)) {
                continue;
            }
            if (preg_match('@^(.*)Func$@', $key, $m)) {
                $this->regFunc($m[1], $value);
                continue;
            }
            if ($value instanceof \Closure) {
                $value = call_user_func($value, $this);
            }
            $this->set($key, $value, true);
        }
        //设置默认值
        $config = Config::get('form.field_default', []);
        foreach ($config as $key => $value) {
            $key = Utils::attrToCamel($key);
            if (empty($key)) {
                continue;
            }
            if (preg_match('@Func$@', $key)) {
                continue;
            }
            $cur_value = $this->get($key, true);
            if (!($cur_value === null || $cur_value === '')) {
                continue;
            }
            if ($value instanceof \Closure) {
                $value = call_user_func($value, $this);
            }
            $this->set($key, $value, true);
        }
        if (empty($this->_attr['id'])) {
            $this->_attr['id'] = $this->name;
        }
        if (empty($this->_attr['name'])) {
            $this->_attr['name'] = $this->name;
        }
    }

    /**
     * 设置属性值
     * @param string $name
     * @param $value
     * @param bool $p 包括原有属性
     */
    private function set(string $name, $value, $p = false)
    {
        if (empty($name)) {
            return;
        }
        if ($p && $this->_ref->hasProperty($name)) {
            $prop = $this->_ref->getProperty($name);
            if ($prop->isPublic()) {
                $prop->setValue($this, $value);
            }
        } else if ($name[0] == 'b' && preg_match('@^box([A-Z].*)$@', $name, $m)) {
            $name = Utils::camelToAttr($m[1]);
            $this->_attr[$name] = $value;
        } else if ($name[0] == 'd' && preg_match('@^data([A-Z].*)$@', $name)) {
            $name = Utils::camelToAttr($name);
            $this->_data[$name] = $value;
        } else if ($name[0] == 'v' && preg_match('@^view([A-Z].*)$@', $name)) {
            $this->_view[$name] = $value;
        } else if ($name == 'value') {
            $this->_value = $value;
        } else {
            $this->_extends[$name] = $value;
        }
    }

    /**
     * 获取属性值
     * @param $name
     * @param bool $p 包括原有属性
     * @return mixed|null
     */
    private function get($name, $p = false)
    {
        if ($p && $this->_ref->hasProperty($name)) {
            $prop = $this->_ref->getProperty($name);
            if ($prop->isPublic()) {
                return $prop->getValue($this);
            }
        } else if ($name[0] == 'b' && preg_match('@^box([A-Z].*)$@', $name, $m)) {
            $name = Utils::camelToAttr($m[1]);
            return isset($this->_attr[$name]) ? $this->_attr[$name] : null;
        } else if ($name[0] == 'd' && preg_match('@^data([A-Z].*)$@', $name)) {
            $name = Utils::camelToAttr($name);
            return isset($this->_data[$name]) ? $this->_data[$name] : null;
        } else if ($name[0] == 'v' && preg_match('@^view([A-Z].*)$@', $name)) {
            return isset($this->_view[$name]) ? $this->_view[$name] : null;
        } else if ($name == 'value') {
            if ($this->_value !== null || $this->form == null || $this->form->getType() != 'add' || $this->default === null || $this->default === '') {
                return $this->_value;
            } else {
                return $this->default;
            }
        } else {
            return isset($this->_extends[$name]) ? $this->_extends[$name] : null;
        }
    }

    /**
     * 注册函数
     * @param string $name
     * @param $func
     */
    public function regFunc(string $name, $func)
    {
        $this->_func[$name] = $func;
    }

    /**
     * 获取函数
     * @param string $name
     * @return mixed|null
     */
    public function getFunc(string $name)
    {
        return isset($this->_func[$name]) ? $this->_func[$name] : null;
    }

    /**
     * 属性赋值
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * 获取属性
     * @param $name
     * @return false|int|mixed|null|string
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * 判断属性存在
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        if ($name[0] == 'b' && preg_match('@^box([A-Z].*)$@', $name, $m)) {
            $name = Utils::camelToAttr($m[1]);
            return isset($this->_attr[$name]);
        } else if ($name[0] == 'd' && preg_match('@^data([A-Z].*)$@', $name)) {
            $name = Utils::camelToAttr($name);
            return isset($this->_data[$name]);
        } else if ($name[0] == 'v' && preg_match('@^view([A-Z].*)$@', $name)) {
            return isset($this->_view[$name]);
        } else if ($name == 'value') {
            return true;
        } else {
            return isset($this->_extends[$name]);
        }
    }

    /**
     * 删除属性
     * @param $name
     * @return mixed
     */
    public function __unset($name)
    {
        if ($name[0] == 'b' && preg_match('@^box([A-Z].*)$@', $name, $m)) {
            $name = Utils::camelToAttr($m[1]);
            unset($this->_attr[$name]);
        } else if ($name[0] == 'd' && preg_match('@^data([A-Z].*)$@', $name)) {
            $name = Utils::camelToAttr($name);
            unset($this->_data[$name]);
        } else if ($name[0] == 'v' && preg_match('@^view([A-Z].*)$@', $name)) {
            unset($this->_view[$name]);
        } else if ($name == 'value') {
            return;
        } else {
            unset($this->_extends[$name]);
        }
    }

    /**
     * 获取表单
     * @return Form|null
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * 获取输入框属性
     */
    public function getAttributes()
    {
        $attr = array_merge($this->_attr, $this->_data);
        $attr = array_filter($attr, function ($v) {
            return $v !== null && $v !== '';
        });
        if ($this->form != null && $this->form->getType() == 'edit') {
            if ($this->offEdit) {
                $attr['disabled'] = 'disabled';
            }
        }
        if ($this->value !== null) {
            $attr['value'] = $this->value;
        }
        return $attr;
    }

    /**
     * 获取插件显示的代码
     * @param null $attr
     * @return mixed|string|void
     * @throws \Exception
     */
    public function code($attr = null)
    {
        if ($attr === null || !is_array($attr)) {
            $attr = [];
        }
        try {
            if ($this->form != null) {
                $this->form->createDynamic($this);
            }
            $box = self::getInstance($this->type);
            if ($box === null) {
                throw new \Exception('Unsupported input box type:' . $this->type);
            }
            if (!empty($this->viewTemplate)) {
                $template = $this->viewTemplate;
                $this->viewTemplate = null;
                $view = new View();
                $view->assign('form', $this->form);
                $view->assign('field', $this);
                $view->assign('attr', $attr);
                $data = $view->fetch($template);
                return $data;
            }
            return $box->code($this, $attr);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * 获取插件实例
     * @param string $type
     * @return WidgetInterface
     * @throws \ReflectionException
     */
    public static function getInstance(string $type)
    {
        if (empty($type)) {
            return null;
        }
        if (isset(self::$instance[$type])) {
            return self::$instance[$type];
        }
        $class = '\\beacon\\widget\\' . Utils::toCamel($type);
        //  Logger::log($class);
        if (!class_exists($class)) {
            return null;
        }
        $reflect = new \ReflectionClass($class);
        if (!$reflect->implementsInterface(WidgetInterface::class)) {
            return null;
        }
        self::$instance[$type] = new $class();
        return self::$instance[$type];
    }


}
