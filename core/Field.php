<?php


namespace beacon\core;

/**
 * 字段类
 * Class Field
 * @package beacon\core
 * @property string $boxId
 * @property string $boxName
 * @property string $name
 */
abstract class Field
{
    /**
     * 所在表单
     * @var Form|null
     */
    protected ?Form $form = null;
    //报告错误
    public string $error = '';

    //用于链接字段
    public ?Field $next = null;
    public ?Field $prev = null;
    protected bool $isInitAttr = false;

    //从属性中取值
    protected mixed $value = null;           //值
    protected string $_name;                //字段名
    public string $varType = '';                        //字段类型
    public mixed $default = null;                       //默认值
    public string|array|null $valFunc = null;           //处理值的函数
    public string|array|null $defaultFunc = null;
    public string $defaultFromParam = '';

    //基础数据
    public string $label = '';            //标题
    public bool $close = false;             //关闭控件
    public bool $viewClose = false;         //视图关闭
    public int $viewMerge = 0;              //合并显示项
    public bool $hidden = false;            //是否隐藏输入框,如果是，将放入表单输入框尾部
    public bool $offEdit = false;           //禁止编辑
    public bool $offJoin = false;           //禁止加入生成数组
    public string $before = '';          //控件前内容
    public string $after = '';           //控件后内容
    public string $prompt = '';            //提示语言
    public bool $star = false;             //是否标星
    public string $tabIndex = '';          //所在标签

    //只有在全动态模式下有效
    public string $warpStyle = '';          //容器宽
    public string $labelStyle = '';        //标题宽
    public string $cellStyle = '';          //单元格样式

    //用于验证的数据
    public array $valid = [];       //验证内容
    //元素属性
    protected array $_attrs = [];   //控件属性
    //用于生成js控制数据
    public ?array $dynamic = null;                 //动态控制
    protected array $yeeModule = [];           //插件数据

    /**
     * 创建字段
     * Field constructor.
     * @param mixed ...$args
     */
    public function __construct(...$args)
    {
        $this->setting($args);
    }

    public function __get(string $property)
    {
        if ($property == 'name') {
            return $this->_name;
        } elseif ($property == 'boxId') {
            return $this->boxId();
        } else if ($property == 'boxName') {
            return $this->boxName();
        }
    }

    /**
     * @param $args
     */
    public function setting(array $args): void
    {
        if (isset($args['label']) && is_string($args['label'])) {
            $this->label = $args['label'];
        }

        if (isset($args['warpStyle']) && is_string($args['warpStyle'])) {
            $this->warpStyle = $args['warpStyle'];
        }
        if (isset($args['labelStyle']) && is_string($args['labelStyle'])) {
            $this->labelStyle = $args['labelStyle'];
        }
        if (isset($args['cellStyle']) && is_string($args['cellStyle'])) {
            $this->cellStyle = $args['cellStyle'];
        }

        if (isset($args['close']) && is_bool($args['close'])) {
            $this->close = $args['close'];
        }
        if (isset($args['viewClose']) && is_bool($args['viewClose'])) {
            $this->viewClose = $args['viewClose'];
        }
        if (isset($args['viewMerge']) && is_int($args['viewMerge'])) {
            $this->viewMerge = $args['viewMerge'];
        }
        if (isset($args['hidden']) && is_bool($args['hidden'])) {
            $this->hidden = $args['hidden'];
        }
        if (isset($args['offEdit']) && is_bool($args['offEdit'])) {
            $this->offEdit = $args['offEdit'];
        }
        if (isset($args['offJoin']) && is_bool($args['offJoin'])) {
            $this->offJoin = $args['offJoin'];
        }
        if (isset($args['before']) && is_string($args['before'])) {
            $this->before = $args['before'];
        }
        if (isset($args['after']) && is_string($args['after'])) {
            $this->after = $args['after'];
        }
        if (isset($args['prompt']) && is_string($args['prompt'])) {
            $this->prompt = $args['prompt'];
        }
        if (isset($args['star']) && is_bool($args['star'])) {
            $this->star = $args['star'];
        }
        if (isset($args['tabIndex']) && is_string($args['tabIndex'])) {
            $this->tabIndex = $args['tabIndex'];
        }
        if (isset($args['valFunc']) && is_callable($args['valFunc'])) {
            $this->valFunc = $args['valFunc'];
        }
        if (isset($args['defaultFunc']) && is_callable($args['defaultFunc'])) {
            $this->defaultFunc = $args['defaultFunc'];
        }
        if (isset($args['defaultFromParam']) && is_string($args['defaultFromParam'])) {
            $this->defaultFromParam = $args['defaultFromParam'];
        }
        if (isset($args['valid']) && is_array($args['valid'])) {
            foreach ($args['valid'] as $key => $val) {
                $this->valid[$key] = $val;
            }
        }
        //动态数据设置
        if (isset($args['attrs']) && is_array($args['attrs'])) {
            foreach ($args['attrs'] as $key => $val) {
                $this->_attrs[$key] = $val;
            }
        }
        foreach ($args as $key => $value) {
            if (preg_match('@^valid([A-Z]\w*)$@', $key, $m)) {
                $attrKey = Util::camelToAttr($m[1]);
                $this->valid[$attrKey] = $value;
            } elseif (preg_match('@^attr([A-Z]\w*)$@', $key, $m)) {
                $attrKey = Util::camelToAttr($m[1]);
                $this->_attrs[$attrKey] = $value;
            }
        }
        //替换属性中的url
        foreach ($this->_attrs as $key => $url) {
            if (is_string($url) && preg_match('@(url|href)$@', $key)) {
                $this->_attrs[$key] = App::url($url);
            }
        }
        //动态数据设置
        if (isset($args['dynamic']) && is_array($args['dynamic'])) {
            $this->dynamic = $args['dynamic'];
        }
        if (isset($args['boxName']) && is_string($args['boxName'])) {
            $this->_attrs['name'] = $args['boxName'];
        }
    }

    /**
     * 初始化
     * @param ?Form $form
     * @param string $name
     * @param string $type
     * @param mixed $default
     */
    public function init(?Form $form, string $name, string $type, mixed $default)
    {
        $this->form = $form;
        $this->_name = $name;
        $this->varType = $type;
        $this->default = $default;
    }

    /**
     * 绑定值
     * @param mixed $value
     */
    public function bindValue(mixed &$value)
    {
        $this->value = &$value;
    }

    /**
     * 设置表单
     * @param Form $form
     */
    public function setForm(Form $form)
    {
        $this->form = $form;
    }

    /**
     * 获取表单
     * @return Form|null
     */
    public function getForm(): ?Form
    {
        return $this->form;
    }

    /**
     * 输入框名称
     * @return string
     */
    protected function boxName(): string
    {
        return $this->_attrs['name'] ?? $this->name;
    }

    /**
     * 获取输入框Id
     * @return string
     */
    protected function boxId(): string
    {
        return $this->_attrs['id'] ?? $this->boxName;
    }


    /**
     * 添加控件数据
     * @param string $module
     */
    public function addYeeModule(string $module)
    {
        $temp = explode(' ', $module);
        foreach ($temp as $item) {
            $item = trim($item);
            if ($item != '') {
                $this->yeeModule[$item] = $item;
            }
        }
    }

    /**
     * 获取控制模块
     * @param ?string $module
     * @return string
     */
    public function getYeeModule(?string $module = null): string
    {
        if (!empty($module)) {
            $this->addYeeModule($module);
        }
        if (count($this->yeeModule) == 0) {
            return '';
        }
        return join(' ', $this->yeeModule);
    }

    /**
     * 获取属性值
     * @return array
     */
    protected function attrs(): array
    {
        if ($this->isInitAttr) {
            return $this->_attrs;
        }
        $this->_attrs['id'] = $this->boxId();
        $this->_attrs['name'] = $this->boxName();
        if ($this->form !== null) {
            if (!isset($this->_attrs['disabled']) && $this->offEdit && $this->form->isEdit()) {
                $this->_attrs['disabled'] = 'disabled';
            }
        }
        $this->_attrs['value'] = $this->getValue();
        //处理验证数据
        if ($this->valid !== null && is_array($this->valid)) {
            foreach ($this->valid as $key => $item) {
                if (in_array($key, ['rule', 'disabled', 'display', 'default', 'correct', 'error'])) {
                    if (is_bool($item) || !empty($item)) {
                        $this->_attrs['data-valid-' . $key] = $item;
                    }
                }
            }
        }
        //处理控制模块
        if (!empty($this->_attrs['yee-module'])) {
            $this->addYeeModule($this->_attrs['yee-module']);
        }
        $module = $this->getYeeModule();
        if (!empty($module)) {
            $this->_attrs['yee-module'] = $this->getYeeModule();
        }
        $this->isInitAttr = true;
        return $this->_attrs;
    }

    /**
     * 设置属性
     * @param string $name
     * @param mixed $value
     */
    public function setAttr(string $name, mixed $value)
    {
        $this->_attrs[$name] = $value;
    }

    /**
     * 获取属性
     * @param string $name
     * @return mixed
     */
    public function getAttr(string $name): mixed
    {
        $this->attrs();
        return $this->_attrs[$name] ?? null;
    }

    /**
     * 渲染数据
     * @param array $attrs
     * @return string
     */
    public function render(array $attrs = []): string
    {
        $this->createDynamic();
        $attrs = array_merge($this->attrs(), $attrs);
        $code = [];
        if (!empty($this->before)) {
            $code[] = '<span class="before"> ' . $this->before . '</span>';
        }
        $code[] = $this->code($attrs);
        if (!empty($this->after)) {
            $code[] = '<span class="after"> ' . $this->after . '</span>';
        }
        return join('', $code);
    }

    /**
     * 实现： 渲染输入框
     * @param array $attrs
     * @return string
     */
    protected function code(array $attrs = []): string
    {
        return static::makeTag('input', ['attrs' => $attrs]);
    }

    /**
     * 是否被排除加入到数据中
     * @return bool
     */
    public function isExclude(): bool
    {
        if ($this->close || $this->offJoin || ($this->form !== null && $this->form->isEdit() && $this->offEdit)) {
            return true;
        }
        return false;
    }

    /**
     * 是否前台可视
     * @return bool
     */
    public function isView(): bool
    {
        if ($this->close || $this->viewClose || $this->hidden) {
            return false;
        }
        return true;
    }

    /**
     * 实现：加入到数据中
     * @param array $data
     */
    public function joinData(array &$data = [])
    {
        $data[$this->name] = $this->getValue();
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function fromData(array $data = []): mixed
    {
        return $data[$this->name] ?? null;
    }

    /**
     * 设置值
     * @param mixed $value
     */
    public function setValue(mixed $value)
    {
        if ($value !== null) {
            $this->value = Util::convertType($value, $this->varType);
        }
    }

    /**
     * 清除值
     */
    public function clearValue()
    {
        $this->value = null;
    }

    /**
     * 获取值
     * @return mixed
     */
    public function getValue(): mixed
    {
        if ($this->form != null && $this->form->isAdd() && $this->value === null) {
            if ($this->default === null) {
                if (!empty($this->defaultFunc) && is_callable($this->defaultFunc)) {
                    $this->default = call_user_func($this->defaultFunc);
                } elseif (!empty($this->defaultFromParam)) {
                    $this->default = Request::param($this->defaultFromParam);
                }
            }
            if ($this->default !== null) {
                $this->value = Util::convertType($this->default, $this->varType);
            }
        }
        return $this->value;
    }

    /**
     * 实现：从参数中获取
     * @param array $param
     * @return mixed
     */
    public function fromParam(array $param = []): mixed
    {
        $name = $this->boxName();
        return Request::lookType($param, $name, $this->varType);
    }


    /**
     * 验证控件
     * @param array $errors
     * @return bool
     */
    public function validate(array &$errors): bool
    {
        if (!empty($field->error)) {
            $errors[$this->name] = $field->error;
            return false;
        }
        if (empty($this->form) || $this->close || ($this->offEdit && $this->form->type == 'edit')) {
            return true;
        }
        $ret = Validator::checkValue($this->getValue(), $this->valid, $error);
        if (!$ret) {
            $errors[$this->name] = $this->error = $error;
            return false;
        }
        return true;
    }

    /**
     * 设置动态数据
     */
    public function createDynamic()
    {

        if (!is_array($this->dynamic)) {
            return;
        }
        if (!empty($this->_attrs['data-dynamic'])) {
            return;
        }
        $dynamic = [];
        foreach ($this->dynamic as $item) {
            $temp = [];
            $hasCondition = false;
            foreach (['eq', 'neq', 'in', 'nin'] as $key) {
                if (!isset($item[$key])) {
                    continue;
                }
                $hasCondition = true;
                $temp[$key] = $item[$key];
            }
            if (!$hasCondition) {
                continue;
            }
            $hasType = false;
            foreach (['hide', 'show', 'off', 'on'] as $type) {
                if (!isset($item[$type])) {
                    continue;
                }
                if (!(is_string($item[$type]) || is_array($item[$type]))) {
                    continue;
                }
                //获取ID数组值
                $tempIds = [];
                //转成数组
                $typeItems = is_string($item[$type]) ? explode(',', $item[$type]) : $item[$type];
                foreach ($typeItems as $name) {
                    if (!is_string($name) || empty($name)) {
                        continue;
                    }
                    if ($this->form != null) {
                        $box = $this->form->getField($name);
                        if ($box == null || empty($box->boxId())) {
                            continue;
                        }
                        $tempIds[] = $box->boxId();
                    } else {
                        $tempIds[] = $name;
                    }
                }
                if (count($tempIds) > 0) {
                    $temp[$type] = $tempIds;
                    $hasType = true;
                }
            }
            if (!$hasType) {
                continue;
            }
            $dynamic[] = $temp;
        }
        //设置 yee-module 属性
        if (count($dynamic) > 0) {
            $this->_attrs['data-dynamic'] = $dynamic;
            $this->addYeeModule('dynamic');
        }
    }

    /**
     * 验证动态数据
     */
    public function validDynamic()
    {
        if ($this->form === null) {
            return;
        }
        if (!is_array($this->dynamic)) {
            return;
        }
        //Logger::log('form empty', $this->dynamic);
        $value = $this->value;
        if (is_object($value)) {
            return;
        }
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        foreach ($this->dynamic as $item) {
            if (!isset($item['eq']) && !isset($item['neq']) && !isset($item['in']) && !isset($item['nin'])) {
                continue;
            }
            if (!isset($item['hide']) && !isset($item['show']) && !isset($item['off']) && !isset($item['on'])) {
                continue;
            }
            //判断相等
            if (isset($item['eq'])) {
                $bVal = $item['eq'];
                if (is_array($bVal)) {
                    $bVal = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                if ($bVal != $value) {
                    continue;
                }
            }
            //判断不等
            if (isset($item['neq'])) {
                $bVal = $item['neq'];
                if (is_array($bVal)) {
                    $bVal = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                if ($bVal == $value) {
                    continue;
                }
            }
            //在集合里面
            if (isset($item['in'])) {
                $bVal = $item['in'];
                if (!is_array($bVal)) {
                    continue;
                }
                $in = false;
                foreach ($bVal as $bItem) {
                    if (strval($bItem) == strval($value)) {
                        $in = true;
                        break;
                    }
                }
                if (!$in) {
                    continue;
                }
            }
            //不在集合里面
            if (isset($item['nin'])) {
                $bVal = $item['nin'];
                if (!is_array($bVal)) {
                    continue;
                }
                $in = false;
                foreach ($bVal as $bItem) {
                    if (strval($bItem) == strval($value)) {
                        $in = true;
                        break;
                    }
                }
                if ($in) {
                    continue;
                }
            }
            //校验item
            $temp = [];
            foreach (['hide', 'off'] as $type) {
                if (!isset($item[$type])) {
                    continue;
                }
                if (!(is_string($item[$type]) || is_array($item[$type]))) {
                    continue;
                }
                //转数组
                $temp[$type] = is_string($item[$type]) ? explode(',', $item[$type]) : $item[$type];
            }
            if (isset($temp['hide'])) {
                foreach ($temp['hide'] as $name) {

                    if (!is_string($name) || empty($name)) {
                        continue;
                    }
                    $box = $this->form->getField($name);
                    if ($box == null || empty($box->boxId())) {
                        continue;
                    }
                    $box->valid['disabled'] = true;
                    $box->close = true;
                }
            }
            if (isset($temp['off'])) {
                foreach ($temp['off'] as $name) {
                    if (!is_string($name) || empty($name)) {
                        continue;
                    }
                    $box = $this->form->getField($name);
                    if ($box == null || empty($box->boxId())) {
                        continue;
                    }
                    $box->valid['disabled'] = true;
                }
            }
        }
    }

    /**
     * 生成代码
     * @param string $tag 标签 如 a
     * @param array{attrs:array,exclude:array,filter:bool,text:string,code:string} $data 数据，attrs:属性值，exclude 排除属性，filter 过滤空属性,默认为真，text 标签内文本，code 标签内代码
     * @return string
     */
    public static function makeTag(string $tag = '', array $data = []): string
    {
        if ($tag == 'input' || $tag == 'img') {
            $begin = '<' . $tag;
            $end1 = '/>';
            $end2 = '';
        } else {
            $begin = '<' . $tag;
            $end1 = '>';
            $end2 = '</' . $tag . '>';
        }
        $base = [];
        $base[] = $begin;
        if (isset($data['attrs'])) {
            $filter = $data['filter'] ?? true;
            $exclude = isset($data['exclude']) && is_array($data['exclude']) ? $data['exclude'] : [];
            foreach ($data['attrs'] as $key => $val) {
                if ($val === null) {
                    continue;
                }
                if ($filter && $val === '') {
                    continue;
                }
                if (count($exclude) > 0 && in_array($key, $exclude)) {
                    continue;
                }
                if (is_array($val) || is_object($val)) {
                    $base[] = $key . '="' . htmlspecialchars(json_encode($val, JSON_UNESCAPED_UNICODE)) . '"';
                } else if (is_bool($val)) {
                    $base[] = $key . '="' . ($val ? 1 : 0) . '"';
                } else if (is_string($val)) {
                    $base[] = $key . '="' . htmlspecialchars($val) . '"';
                } else {
                    $base[] = $key . '="' . $val . '"';
                }
            }
            $base = [join(' ', $base)];
        }
        $base[] = $end1;
        if (isset($data['text']) && $data['text'] !== '') {
            $text = $data['text'];
            if (is_array($text) || is_object($text)) {
                $base[] = htmlspecialchars(json_encode($text, JSON_UNESCAPED_UNICODE));
            } else if (is_bool($text)) {
                $base[] = ($text ? 1 : 0);
            } else if (is_string($text)) {
                $base[] = htmlspecialchars($text);
            } else {
                $base[] = $text;
            }
        }
        if (isset($data['code']) && $data['code'] !== '') {
            $base[] = $data['code'];
        }
        $base[] = $end2;
        return join('', $base);
    }


    /**
     * 使用参数创建字段
     * @param array{name:string,type:string} $param
     * @return Field
     * @throws \Exception
     */
    public static function create(array $param): Field
    {
        if (empty($param['name'])) {
            throw new \Exception('the field name is empty!');
        }
        $name = $param['name'];
        $type = empty($param['type']) ? 'Text' : $param['type'];
        $class = '\\beacon\widget\\' . $type;
        unset($param['type']);
        if (!class_exists($class)) {
            throw new \Exception($class . ' is not found.');
        }
        $args = static::getTagArgs($param);
        $varType = $args['varType'] ?? 'string';
        unset($args['varType']);
        $field = new $class(...$args);
        $field->init(null, $name, $varType, null);
        if (isset($args['value'])) {
            $field->bindValue($args['value']);
        }
        return $field;
    }

    /**
     * 从模板标签中获取参数
     * @param array $param
     * @return array
     */
    public static function getTagArgs(array $param): array
    {
        $args = [];
        foreach ($param as $key => $item) {
            if ($key[0] == '@') {
                $key = substr($key, 1);
                $args[$key] = $item;
            }
        }
        foreach ($param as $key => $item) {
            if ($key[0] != '@') {
                if (!isset($args['attrs'])) {
                    $args['attrs'] = [];
                }
                $key = Util::camelToAttr($key);
                $args['attrs'][$key] = $item;
            }
        }
        return $args;
    }
}