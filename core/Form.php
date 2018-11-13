<?php

namespace beacon;

use beacon\widget\BoxInterface;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 15:41
 */
class Form
{
    /**
     * @var $boxInstance \beacon\widget\BoxInterface[]
     */
    private static $boxInstance = [];
    /**
     * @var $fields Field[];
     */
    public $fields = [];

    /**
     * 维护的数据库id
     * @var int
     */
    public $id = 0;
    /**
     * 当前维护的数据表名
     * @var string
     */
    public $tbName = '';

    /**
     * 表单标题
     * @var string
     */
    public $title = '';
    /**
     * 提交类型
     * @var string
     */
    private $type = '';

    /**
     * @var Validate
     */
    private $validate = null;


    /**
     * 隐藏输入框
     * @var array
     */
    protected $hideBox = [];

    /**
     * 自动获取的值
     * @var null
     */
    private $_values = null;

    /**
     * 指定显示的tab
     * @var string|null;
     */
    public $tabIndex = null;
    /**
     * 当前使用字段
     * @var array
     */
    private $tabFields = [];

    /**
     * @param string $type
     * @return \beacon\widget\BoxInterface
     * @throws \Exception
     */
    public static function getBoxInstance(string $type)
    {
        if (empty($type)) {
            return null;
        }
        if (isset(self::$boxInstance[$type])) {
            return self::$boxInstance[$type];
        }
        $class = '\\beacon\\widget\\' . Utils::toCamel($type);
        if (!class_exists($class)) {
            return null;
        }
        $reflect = new \ReflectionClass($class);
        if (!$reflect->implementsInterface(BoxInterface::class)) {
            return null;
        }
        self::$boxInstance[$type] = new $class();
        return self::$boxInstance[$type];
    }


    public function __construct(string $type = '', $tabIndex = null)
    {
        $this->type = empty($type) ? 'add' : $type;
        $this->tabIndex = $tabIndex;
        //加载数据
        $load = $this->load();
        if (is_array($load)) {
            foreach ($load as $name => $field) {
                if (!is_array($field)) {
                    continue;
                }
                $this->addField($name, $field);
            }
        }
    }

    /**
     * 需要继承的
     * @return array
     */
    protected function load()
    {
        return [];
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * 添加字段
     * @param string $name
     * @param $field
     * @param string|null $before
     * @return $this
     */
    public function addField(string $name, $field, string $before = null)
    {
        if ($field instanceof Field) {
            $field->name = $name;
        } else if (is_array($field)) {
            $field['name'] = $name;
            $field = new Field($this, $field);
        } else {
            return $this;
        }
        if (!empty($before) && isset($this->fields[$before])) {
            $temps = [];
            foreach ($this->fields as $key => $item) {
                if ($key == $before) {
                    $temps[$name] = $field;
                }
                $temps[$key] = $item;
            }
            $this->fields = $temps;
        } else {
            $this->fields[$name] = $field;
        }
        return $this;
    }

    /**
     * 获取字段
     * @param string $name
     * @return Field|null
     */
    public function getField(string $name)
    {
        return isset($this->fields[$name]) ? $this->fields[$name] : null;
    }

    /**
     * 删除字段
     * @param string $name
     * @return Field|null
     */
    public function removeField(string $name)
    {
        $field = isset($this->fields[$name]) ? $this->fields[$name] : null;
        if ($field !== null) {
            unset($this->fields[$name]);
        }
        return $field;
    }

    /**
     * 获取错误
     * @param string $name
     * @return null|string
     */
    public function getError(string $name)
    {
        return isset($this->fields[$name]) ? $this->fields[$name]->error : '';
    }

    /**
     * 设置错误
     * @param $name
     * @param $error
     */
    public function setError($name, $error)
    {
        if (isset($this->fields[$name])) {
            $this->fields[$name]->error = $error;
        }
    }

    /**
     * 删除错误
     * @param $name
     */
    public function removeError($name)
    {
        if (isset($this->fields[$name])) {
            $this->fields[$name]->error = null;
        }
    }

    /**
     * 获取第一条错误
     * @return null
     */
    public function getFirstError()
    {
        $fields = $this->getTabFields();
        foreach ($fields as $name => $field) {
            if (!empty($field->error)) {
                return $field->error;
            }
        }
        return null;
    }

    /**
     * 获取所有错误
     * @return array
     */
    public function getAllError()
    {
        $errors = [];
        $fields = $this->getTabFields();
        foreach ($fields as $name => $field) {
            if (!empty($field->error)) {
                $errors[$name] = $field->error;
            }
            if ($field->dataValOff == false) {
                if (!empty($field->childError) && is_array($field->childError)) {
                    foreach ($field->childError as $key => $err) {
                        $errors[$key] = $err;
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * 清除所有错误
     */
    public function clearAllErrors()
    {
        $fields = $this->getTabFields();
        foreach ($fields as $name => $field) {
            if (!empty($field->error)) {
                $field->error = null;
            }
        }
    }

    /**
     * 添加一个隐藏输入框
     * @param string $name
     * @param $value
     */
    public function addHideBox(string $name, $value)
    {
        $this->hideBox[$name] = $value;
    }

    /**
     * 获取一个隐藏输入框的值
     * @param string|null $name
     * @return array|mixed
     */
    public function getHideBox(string $name = null)
    {
        if (empty($name)) {
            return $this->hideBox;
        }
        if (isset($this->hideBox[$name])) {
            return $this->hideBox[$name];
        }
    }

    /**
     * 输出隐藏输入框
     * @return string
     */
    public function fetchHideBox()
    {
        $box = [];
        foreach ($this->hideBox as $key => $val) {
            $box[] = '<input type="hidden" name="id" value="' . htmlspecialchars($val, ENT_QUOTES) . '">';
        }
        return join('', $box);
    }

    /**
     * 获取当前tab 下的字段
     * @param string|null $tabIndex
     * @return Field[]
     */
    public function getTabFields(string $tabIndex = null)
    {
        $tabIndex = empty($tabIndex) ? $this->tabIndex : null;
        if (empty($tabIndex)) {
            return $this->fields;
        }
        if (isset($this->tabFields[$tabIndex])) {
            return $this->tabFields[$tabIndex];
        }
        $temp = [];
        foreach ($this->fields as $name => $field) {
            if (!empty($field->tabIndex) && $field->tabIndex == $tabIndex) {
                $temp[$name] = $field;
            }
        }
        $this->tabFields[$tabIndex] = $temp;
        return $temp;
    }

    /**
     * 获取插件字段
     * @return Field[]
     */
    public function getPlugFields()
    {
        $fields = $this->getTabFields();
        $plugins = [];
        foreach ($fields as $field) {
            if ($field->type != 'plugin' || !$field->autoSave || empty($field->referenceField)) {
                continue;
            }
            $plugins[] = $field;
        }
        return $plugins;
    }

    /**
     * 获取表单值
     * @param bool $force 是否强制重新获取
     * @return array|null
     * @throws \Exception
     */
    public function getValues(bool $force = false)
    {
        if (!$force && $this->_values !== null) {
            return $this->_values;
        }
        $values = [];
        $fields = $this->getTabFields();
        foreach ($fields as $name => $field) {
            if ($field->notSave) {
                continue;
            }
            if ($field->close || ($field->offEdit && $this->type == 'edit')) {
                continue;
            }
            $box = self::getBoxInstance($field->type);
            if ($box != null) {
                $box->fill($field, $values);
            } else {
                $values[$name] = $field->value;
            }
        }
        $this->_values = $values;
        return $values;
    }

    /**
     * 设置表单值
     * @param array|null $values
     * @param bool $force
     * @throws \Exception
     */
    public function setValues(array $values = null, bool $force = false)
    {
        if ($values == null) {
            return;
        }
        $fields = $this->getTabFields();
        if (method_exists($this, 'beforeInitValues')) {
            $this->beforeInitValues($fields, $values);
        }
        if (isset($values['id'])) {
            $this->id = intval($values['id']);
        }
        foreach ($fields as $name => $field) {
            if ($field->close) {
                continue;
            }
            if (!$force && $field->value !== null) {
                continue;
            }
            $box = self::getBoxInstance($field->type);
            if ($box != null) {
                $box->init($field, $values);
            } else {
                $field->value = isset($values[$name]) ? $values[$name] : null;
            }
        }

        if (method_exists($this, 'afterInitValues')) {
            $this->afterInitValues($fields, $values);
        }
    }

    /**
     * 清空表单值
     */
    public function clearValues()
    {
        $this->_values = null;
        $fields = $this->fields;
        foreach ($fields as $name => $field) {
            $field->value = null;
        }
    }

    /**
     * 自动完成表单提取
     * @param string $method
     * @return array
     * @throws \Exception
     */
    public function autoComplete(string $method = 'post')
    {
        $method = strtolower($method);
        $data = $_REQUEST;
        if ($method == 'get') {
            $data = $_GET;
        } elseif ($method == 'post') {
            $data = $_POST;
        }
        return $this->fillComplete($data);
    }

    /**
     * 使用 数组填充
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function fillComplete(array $data)
    {
        $fields = $this->getTabFields();
        $request = Request::instance();
        $valueFuncFields = [];
        foreach ($fields as $name => $field) {
            if ($field->close || ($field->offEdit && $this->type == 'edit')) {
                continue;
            }
            if (!empty($field->valueFunc) && is_callable($field->valueFunc)) {
                $valueFuncFields[] = $field;
            }
            if ($field->viewClose) {
                $field->value = $field->default;
                continue;
            }
            $box = self::getBoxInstance($field->type);
            if ($box == null) {
                $box = self::getBoxInstance('hidden');
            }
            $box->assign($field, $data);
            if ($field->forceDefault && !empty($field->default) && (is_string($field->value) || is_int($field->value) || is_double($field->value) || is_float($field->value) || is_array($field->value) || $field->value === null) && empty($field->value)) {
                $field->value = $field->default;
            }
        }
        foreach ($valueFuncFields as $field) {
            $field->value = call_user_func($field->valueFunc, $this);
        }
        return $this->getValues();
    }

    /**
     * 验证表单
     * @param null $errors
     * @return bool
     */
    public function validation(&$errors = null)
    {
        $fields = $this->getTabFields();
        if (method_exists($this, 'beforeValid')) {
            $this->beforeValid($fields);
        }
        $result = true;
        foreach ($fields as $field) {
            $this->validDynamic($field);
        }
        foreach ($fields as $name => $field) {
            if (!empty($field->error)) {
                $result = false;
                continue;
            }
            if ($field->close || ($field->offEdit && $this->type == 'edit')) {
                continue;
            }
            $ret = $this->getValidate()->checkField($field);
            if (!$ret) {
                $result = false;
            }
        }
        if (method_exists($this, 'afterValid')) {
            $this->afterValid($fields);
        }
        $errors = $this->getAllError();
        return $result;
    }

    /**
     * 获取表单验证器
     * @return Validate
     */
    public function getValidate()
    {
        if ($this->validate == null) {
            $this->validate = new Validate();
        }
        return $this->validate;
    }

    /**
     * 创建动态字段数据
     * @param Field $field
     */
    public function createDynamic(Field $field)
    {
        if ($field->dynamic === null || !is_array($field->dynamic)) {
            return;
        }
        if (!isset($field->dataDynamic) || empty($field->dataDynamic)) {
            $dynamic = [];
            foreach ($field->dynamic as $item) {
                $temp = [];
                $hasCondition = false;
                foreach (['eq', 'neq', 'in', 'nin'] as $qkey) {
                    if (!isset($item[$qkey])) {
                        continue;
                    }
                    $hasCondition = true;
                    $temp[$qkey] = $item[$qkey];
                }
                if (!$hasCondition) {
                    continue;
                }
                $hasType = false;
                foreach (['hide', 'show', 'off-val', 'on-val'] as $type) {
                    if (!isset($item[$type])) {
                        continue;
                    }
                    if (!(is_string($item[$type]) || is_array($item[$type]))) {
                        continue;
                    }
                    //获取ID数组值
                    $tempIds = [];
                    //转成数组
                    $typeitems = is_string($item[$type]) ? explode(',', $item[$type]) : $item[$type];
                    foreach ($typeitems as $name) {
                        if (!is_string($name) || empty($name)) {
                            continue;
                        }
                        $box = $this->getField($name);
                        if ($box == null || empty($box->boxId)) {
                            continue;
                        }
                        $tempIds[] = $box->boxId;
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
                $field->dataDynamic = $dynamic;
            }
        }
        if (isset($field->dataDynamic) && count($field->dataDynamic) > 0) {
            if (isset($field->boxYeeModule)) {
                $module = explode(' ', $field->boxYeeModule);
                $module = array_filter($module, 'strlen');
                if (!in_array('dynamic', $module)) {
                    $module[] = 'dynamic';
                }
                $field->boxYeeModule = join(' ', $module);
            } else {
                $field->boxYeeModule = 'dynamic';
            }
        }
    }

    /**
     * 验证动态字段数据
     * @param Field $field
     */
    private function validDynamic(Field $field)
    {
        if ($field->dynamic === null || !is_array($field->dynamic)) {
            return;
        }
        //  $fields = $this->getTabFields();
        $value = $field->value;
        if (is_object($value)) {
            return;
        }
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        foreach ($field->dynamic as $item) {
            if (!isset($item['eq']) && !isset($item['neq']) && !isset($item['in']) && !isset($item['nin'])) {
                continue;
            }
            if (!isset($item['hide']) && !isset($item['show']) && !isset($item['off-val']) && !isset($item['on-val'])) {
                continue;
            }
            //判断相等
            if (isset($item['eq'])) {
                $bval = $item['eq'];
                if (is_array($bval)) {
                    $bval = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                if ($bval != $value) {
                    continue;
                }
            }
            //判断不等
            if (isset($item['neq'])) {
                $bval = $item['neq'];
                if (is_array($bval)) {
                    $bval = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                if ($bval == $value) {
                    continue;
                }
            }
            //在集合里面
            if (isset($item['in'])) {
                $bval = $item['in'];
                if (!is_array($bval)) {
                    continue;
                }
                $in = false;
                foreach ($bval as $bitem) {
                    if (strval($bitem) == strval($value)) {
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
                $bval = $item['nin'];
                if (!is_array($bval)) {
                    continue;
                }
                $in = false;
                foreach ($bval as $bitem) {
                    if (strval($bitem) == strval($value)) {
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

            foreach (['hide', 'off-val'] as $type) {
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
                    $box = $this->getField($name);
                    if ($box == null || empty($box->boxId)) {
                        continue;
                    }
                    $box->dataValOff = true;
                    $box->close = true;
                }
            }
            if (isset($temp['off-val'])) {
                foreach ($temp['off-val'] as $name) {
                    if (!is_string($name) || empty($name)) {
                        continue;
                    }
                    $box = $this->getField($name);
                    if ($box == null || empty($box->boxId)) {
                        continue;
                    }
                    $box->dataValOff = true;
                }
            }
        }

    }

    /**
     * 获取要显示的字段
     * @param null $tabIndex
     * @return array
     */
    public function getViewFields($tabIndex = null)
    {
        $fields = $this->getTabFields($tabIndex);

        //修正显示
        foreach ($fields as $tabIndex => $field) {
            //处理视图的开关默认值
            if ($field->viewClose === null) {
                if ($field->close) {
                    $field->viewClose = true;
                    continue;
                } else {
                    $field->viewClose = false;
                }
            }
            //隐藏字段
            if ($field->type == 'hide') {
                $field->viewClose = true;
                $this->addHideBox($field->boxName, $field->_value);
            }
        }
        $keys = array_keys($fields);
        $temp = [];
        for ($idx = 0, $len = count($keys); $idx < $len; $idx++) {
            $tabIndex = $keys[$idx];
            $field = $fields[$tabIndex];
            if ($field->hideBox) {
                $this->addHideBox($field->boxName, $field->_value);
                continue;
            }
            if ($field->close || $field->viewClose) {
                continue;
            }
            if ($idx == 0) {
                $field->viewMerge = 0;
            }
            //如果这一行合并到上一行
            if ($field->viewMerge == -1) {
                if ($idx - 1 >= 0) {
                    $prevField = $fields[$keys[$idx - 1]];
                    $prevField->next = $field;
                } else {
                    $field->viewMerge = 0;
                }
            }
            //合并到下一行
            if ($field->viewMerge == 1) {
                if ($idx + 1 < $len) {
                    $nextField = $fields[$keys[$idx + 1]];
                    $nextField->prev = $field;
                } else {
                    $field->viewMerge = 0;
                }
            }
            //不合并
            if ($field->viewMerge == 0 && !$field->viewClose) {
                $temp[$tabIndex] = $field;
            }
            if (!$field->viewClose) {
                $this->createDynamic($field);
            }
        }
        return $temp;
    }

    /**
     * 获取一个输入框
     * @param $name
     * @param null $type
     * @param array|null $args
     * @throws \Exception
     */
    public function box($name, $type = null, array $args = null)
    {
        if ($name === null) {
            throw new \Exception('必须指定名称，或者字段');
        }
        if ($args === null && is_array($type)) {
            $args = $type;
            $type = null;
        }
        if (is_string($name)) {
            $field = $this->getField($name);
        } elseif ($name instanceof Field) {
            $field = $name;
        } else {
            throw new \Exception('错误的参数');
        }
        if ($args === null && !is_array($args)) {
            $args = [];
        }
        if ($field == null) {
            $field = new Field($this);
            if (is_string($name)) {
                $field->name = $name;
                $field->boxName = $name;
                $field->boxId = $name;
            }
        }
        if ($type !== null) {
            $field->type = $type;
        }
        return $field->box($args);
    }

    //添加值,联动添加
    public function insert($values = [])
    {
        if (empty($this->tbName)) {
            return;
        }
        $plugins = $this->getPlugFields();
        $vals = $this->getValues();
        $vals = array_merge($vals, $values);
        if (empty($plugins)) {
            DB::insert($this->tbName, $vals);
            $id = DB::lastInsertId();
            $this->id = $id;
            return $id;
        }
        try {
            DB::beginTransaction();
            DB::insert($this->tbName, $vals);
            $id = DB::lastInsertId();
            $this->id = $id;
            foreach ($plugins as $field) {
                Plugin::insert($field, $id);
            }
            DB::commit();
            return $id;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        return $id;
    }

    //编辑值 联动编辑
    public function update($id = 0, $values = [])
    {
        if (empty($this->tbName)) {
            return;
        }
        $this->id = $id;
        $plugins = $this->getPlugFields();
        $vals = $this->getValues();
        $vals = array_merge($vals, $values);
        if (empty($plugins)) {
            DB::update($this->tbName, $vals, $id);
            return $id;
        }
        try {
            DB::beginTransaction();
            DB::update($this->tbName, $vals, $id);
            //查询原有的数据---
            foreach ($plugins as $field) {
                Plugin::update($field, $id);
            }
            DB::commit();
            return $id;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        return 0;
    }

    //获取单行数据,联动获取数据
    public function getRow($id = 0)
    {
        $this->id = $id;
        if (empty($this->tbName)) {
            return null;
        }
        $plugins = $this->getPlugFields();
        $row = DB::getRow('select * from `' . $this->tbName . '` where id=?', $id);
        if (empty($plugins)) {
            return $row;
        }
        foreach ($plugins as $field) {
            $item = Plugin::getData($field, $id);
            if ($item !== null) {
                $row[$field->name] = $item;
            }
        }
        return $row;
    }

    //删除值 联动删除
    public function delete($id)
    {
        $this->id = $id;
        if (empty($this->tbName)) {
            return;
        }
        $plugins = $this->getPlugFields();
        if (empty($plugins)) {
            DB::delete($this->tbName, $id);
            return $id;
        }
        try {
            DB::beginTransaction();
            DB::delete($this->tbName, $id);
            foreach ($plugins as $field) {
                Plugin::delete($field, $id);
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        return $id;
    }

}