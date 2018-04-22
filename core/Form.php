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
     * @var $fields Field[];
     */
    public $fields = [];
    /**
     * @var $boxInstance \beacon\widget\BoxInterface[]
     */
    private static $boxInstance = [];

    public $id = 0;
    //基本属性
    public $title = '';
    public $caption = '';
    public $tbname = '';
    private $type = '';
    public $useAjax = false;
    public $viewNotBack = false;

    //视图属性
    public $viewUseTab = false;
    public $viewTabs = [];
    public $viewCurrentTabIndex = '';
    public $viewTabSplit = false;
    public $viewScript = null;
    /**
     * @var Validate
     */
    private $validate = null;

    private $inited = false;

    private $cacheUsingFields = [];
    protected $hideBox = [];

    private $_values = null;

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

    /**
     * @param string $className
     * @param string $type
     * @return Form|null
     */
    public static function instance(string $className, string $type = '')
    {
        if (!class_exists($className)) {
            return null;
        }
        return new $className($type);
    }

    public function __construct(string $type = '')
    {
        $this->type = empty($type) ? 'add' : $type;
    }

    public function initialize()
    {
        if ($this->inited) {
            return;
        }
        $this->inited = true;
        $load = $this->load();
        if ($this->isEdit()) {
            $loadEdit = $this->loadEdit();
            $load = Utils::replaceItems($load, $loadEdit);
        }
        if (is_array($load)) {
            foreach ($load as $name => $field) {
                if (!is_array($field)) {
                    continue;
                }
                $this->addField($name, $field);
            }
        }
    }

    protected function load()
    {
        return [];
    }

    protected function loadEdit()
    {
        return [];
    }

    public function isAdd()
    {
        return $this->type === 'add';
    }

    public function getType()
    {
        return $this->type;
    }

    public function isEdit()
    {
        return $this->type === 'edit';
    }

    public function addField(string $name, $field, string $before = null)
    {
        $this->initialize();
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
     * @param string $name
     * @return Field|null
     */
    public function getField(string $name)
    {
        $this->initialize();
        return isset($this->fields[$name]) ? $this->fields[$name] : null;
    }

    public function removeField(string $name)
    {
        $this->initialize();
        $field = isset($this->fields[$name]) ? $this->fields[$name] : null;
        if ($field !== null) {
            unset($this->fields[$name]);
        }
        return $field;
    }

    public function getError(string $name)
    {
        $this->initialize();
        return isset($this->fields[$name]) ? $this->fields[$name]->error : '';
    }

    public function setError($name, $error)
    {
        $this->initialize();
        if (isset($this->fields[$name])) {
            $this->fields[$name]->error = $error;
        }
    }

    public function removeError($name)
    {
        $this->initialize();
        if (isset($this->fields[$name])) {
            $this->fields[$name]->error = null;
        }
    }

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

    public function cleanAllErrors()
    {
        $fields = $this->getTabFields();
        foreach ($fields as $name => $field) {
            if (!empty($field->error)) {
                $field->error = null;
            }
        }
    }

    public function emptyFieldsValue()
    {
        $this->initialize();
        $fields = $this->fields;
        foreach ($fields as $name => $field) {
            $field->value = null;
        }
    }

    public function addHideBox(string $name, $value)
    {
        $this->hideBox[$name] = $value;
    }

    public function getHideBox(string $name = null)
    {
        if (empty($name)) {
            return $this->hideBox;
        }
        if (isset($this->hideBox[$name])) {
            return $this->hideBox[$name];
        }
    }

    public function fetchHideBox()
    {
        $box = [];
        foreach ($this->hideBox as $key => $val) {
            $box[] = '<input type="hidden" name="id" value="' . htmlspecialchars($val, ENT_QUOTES) . '">';
        }
        return join('', $box);
    }

    /**
     * @param string|null $tabIndex
     * @return Field[]
     */
    public function getTabFields(string $tabIndex = null)
    {
        $this->initialize();
        if (empty($tabIndex)) {
            if ($this->viewUseTab && $this->viewTabSplit) {
                if (!empty($this->viewCurrentTabIndex)) {
                    $this->viewCurrentTabIndex = Request::instance()->get('tabIndex:s');
                    $tabIndex = $this->viewCurrentTabIndex;
                }
            }
            if (empty($tabIndex)) {
                return $this->fields;
            }
        }
        if (isset($this->cacheUsingFields[$tabIndex])) {
            return $this->cacheUsingFields[$tabIndex];
        }
        $temp = [];
        foreach ($this->fields as $name => $field) {
            if (!empty($field->viewTabIndex) && $field->viewTabIndex == $tabIndex) {
                $temp[$name] = $field;
            }
        }
        $this->cacheUsingFields[$tabIndex] = $temp;
        return $temp;
    }

    /**
     * @return Field[]
     */
    public function getPluginFields()
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

    public function initValues(array $values = null, bool $force = false)
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
            if ($box != null) {
                $box->assign($field, $data);
            } else {
                $boxName = $field->boxName;
                switch ($field->varType) {
                    case 'bool':
                    case 'boolean':
                        $field->value = $request->input($data, $boxName . ':b', false);
                        break;
                    case 'int':
                    case 'integer':
                        $val = $request->input($data, $boxName . ':s', 0);
                        if (preg_match('@[+-]?\d*\.\d+@', $field->default)) {
                            $field->value = $request->input($data, $boxName . ':f', 0);
                        } else {
                            $field->value = $request->input($data, $boxName . ':i', 0);
                        }
                        break;
                    case 'double':
                    case 'float':
                        $field->value = $request->input($data, $boxName . ':f', 0);
                        break;
                    case 'string':
                        $field->value = $request->input($data, $boxName . ':s', '');
                        break;
                    case 'array':
                        $field->value = $request->input($data, $boxName . ':a', []);
                        break;
                    default :
                        $field->value = $request->input($data, $boxName, '');
                        break;
                }
            }
        }
        foreach ($valueFuncFields as $field) {
            $field->value = call_user_func($field->valueFunc, $this);
        }
        return $this->getValues();
    }

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
            $ret = $this->getValidateInstance()->checkField($field);
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

    public function getValidateInstance()
    {
        if ($this->validate == null) {
            $this->validate = new Validate();
        }
        return $this->validate;
    }

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

    public function getViewFields($name = null)
    {
        $fields = $this->getTabFields($name);

        //修正显示
        foreach ($fields as $name => $field) {
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
            $name = $keys[$idx];
            $field = $fields[$name];
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
                $temp[$name] = $field;
            }
            if (!$field->viewClose) {
                $this->createDynamic($field);
            }
        }
        return $temp;
    }

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

    //添加值
    public function insert($values = [])
    {
        if (empty($this->tbname)) {
            return;
        }
        $plugins = $this->getPluginFields();
        $vals = $this->getValues();
        $vals = array_merge($vals, $values);
        $id = 0;
        if (empty($plugins)) {
            DB::insert($this->tbname, $vals);
            $id = DB::lastInsertId();
            $this->id = $id;
            return $id;
        }
        try {
            DB::beginTransaction();
            DB::insert($this->tbname, $vals);
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

    //编辑值
    public function update($id = 0, $values = [])
    {
        if (empty($this->tbname)) {
            return;
        }
        $this->id = $id;
        $plugins = $this->getPluginFields();
        $vals = $this->getValues();
        $vals = array_merge($vals, $values);
        if (empty($plugins)) {
            DB::update($this->tbname, $vals, $id);
            return $id;
        }
        try {
            DB::beginTransaction();
            DB::update($this->tbname, $vals, $id);
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

    //获取单行数据
    public function getRow($id = 0)
    {
        $this->id = $id;
        if (empty($this->tbname)) {
            return null;
        }
        $plugins = $this->getPluginFields();
        $row = DB::getRow('select * from `' . $this->tbname . '` where id=?', $id);
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

    //删除值
    public function delete($id)
    {
        $this->id = $id;
        if (empty($this->tbname)) {
            return;
        }
        $plugins = $this->getPluginFields();
        if (empty($plugins)) {
            DB::delete($this->tbname, $id);
            return $id;
        }
        try {
            DB::beginTransaction();
            DB::delete($this->tbname, $id);
            foreach ($plugins as $field) {
                Plugin::delete($field, $id);
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        return $id;
    }

    public function maxSort()
    {
        if (empty($this->tbname)) {
            return 0;
        }
        if (DB::existsField($this->tbname, 'sort')) {
            return DB::getMax($this->tbname, 'sort') + 10;
        }
        return 0;
    }

    public function minSort()
    {
        if (empty($this->tbname)) {
            return 0;
        }
        if (DB::existsField($this->tbname, 'sort')) {
            return DB::getMin($this->tbname, 'sort') - 10;
        }
        return 0;
    }
}