<?php


namespace beacon\core;

use sdopx\lib\Raw;
use \ReflectionClass;

#[\Attribute]
class Form
{
    const ADD = 'add';
    const EDIT = 'edit';

    /**
     * @var Field[]
     */
    public array $fields = [];
    public string $type = '';
    public object|string $object;
    public string $title = '';
    public string $table = '';
    public string $template = '';
    public string $tabIndex = '';
    protected array $tabFields = [];
    /**
     * @var Form[]
     */
    protected array $mergeForms = [];

    /**
     * 隐藏输入框
     * @var array
     */
    protected array $hideBox = [];
    protected ?array $values = null;


    /**
     * 解析类别
     * @param object|string $object
     * @param string $type
     * @param string $tabIndex
     * @return static|null
     */
    public static function create(object|string $object, string $type = '', string $tabIndex = ''): ?static
    {
        try {
            $refClass = new ReflectionClass($object);
            $temp = $refClass->getAttributes(static::class);
            if (isset($temp[0])) {
                $form = $temp[0]->newInstance();
                if ($form instanceof Form) {
                    $form->init($object, $type, $tabIndex);
                    return $form;
                }
            }
            return null;
        } catch (\ReflectionException) {
            return null;
        }
    }

    /**
     * 表单构造函数.
     * @param string $title
     * @param string $table
     * @param string $template
     */
    public function __construct(string $title = '', string $table = '', string $template = '')
    {
        $this->title = $title;
        $this->table = $table;
        $this->template = $template;
    }

    /**
     * 初始化绑定数据
     * @param object|string $object
     * @param string $type
     * @param string $tabIndex
     * @throws \ReflectionException
     */
    public function init(object|string $object, string $type = '', string $tabIndex = '')
    {
        $this->type = $type;
        $this->object = $object;
        $this->tabIndex = $tabIndex;
        $refClass = new ReflectionClass($object);
        $parameters = $refClass->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($parameters as $parameter) {
            if (!$parameter->isStatic()) {
                $attributes = $parameter->getAttributes();
                if (count($attributes) > 0) {
                    $field = $attributes[0]->newInstance();
                    $name = $parameter->getName();
                    $type = strval($parameter->getType());
                    $default = null;
                    if ($parameter->hasDefaultValue()) {
                        $default = $parameter->getDefaultValue();
                    }
                    $field->init($this, $name, $type, $default);
                    if (is_object($object)) {
                        $field->bindValue($object->$name);
                    }
                    $this->fields[$name] = $field;
                }
            }
        }
    }

    /**
     * 获取表单类型
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * 是否编辑状态
     * @return bool
     */
    public function isEdit(): bool
    {
        return $this->type == 'edit';
    }

    /**
     * 是否添加状态
     * @return bool
     */
    public function isAdd(): bool
    {
        return $this->type == 'add';
    }

    /**
     * 获取字段信息
     * @param string $name
     * @return Field|null
     */
    public function getField(string $name): ?Field
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * 获取当前tab 下的字段
     * @param string $tabIndex
     * @return Field[]
     */
    public function getFields(string $tabIndex = ''): array
    {
        $tabIndex = empty($tabIndex) ? $this->tabIndex : $tabIndex;
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
     * 获取第一条错误
     * @return string
     */
    public function getFirstError(): string
    {
        $fields = $this->getFields();
        foreach ($fields as $field) {
            if (property_exists($field, 'childError') && isset($field->childError) && is_array($field->childError)) {
                foreach ($field->childError as $error) {
                    if (!empty($error)) {
                        return $error;
                    }
                }
            }
            if (!empty($field->error)) {
                return $field->error;
            }
        }
        return '';
    }

    /**
     * 获取所有错误
     * @return string[]
     */
    public function getErrors(): array
    {
        $errors = [];
        $fields = $this->getFields();
        foreach ($fields as $name => $field) {
            if (property_exists($field, 'childError') && isset($field->childError) && is_array($field->childError)) {
                foreach ($field->childError as $childName => $error) {
                    if (!empty($error)) {
                        $errors[$childName] = $error;
                    }
                }
            }
            if (!empty($field->error)) {
                $errors[$name] = $field->error;
            }
        }
        return $errors;
    }

    /**
     *清除所有错误
     */
    public function clearErrors()
    {
        $fields = $this->getFields();
        foreach ($fields as $name => $field) {
            if (property_exists($field, 'childError') && isset($field->childError) && is_array($field->childError)) {
                $field->childError = [];
            }
            if (!empty($field->error)) {
                $field->error = '';
            }
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setHideBox(string $name, mixed $value)
    {
        $this->hideBox[$name] = $value;
    }

    /**
     * 输出隐藏输入框
     * @param string $tabIndex
     * @return Raw
     */
    public function fetchHideBox(string $tabIndex = ''): Raw
    {
        $fields = $this->getFields($tabIndex);
        foreach ($fields as $field) {
            if (isset($field->hidden) && $field->hidden == true) {
                $this->setHideBox($field->boxName, $field->getValue());
            }
        }
        $box = [];
        foreach ($this->hideBox as $name => $val) {
            $box[] = '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES) . '" value="' . htmlspecialchars($val, ENT_QUOTES) . '">';
        }
        return new Raw(join('', $box));
    }

    /**
     * 获取值
     * @param bool $anew 是否重新获取
     * @return array
     */
    public function getData(bool $anew = false): array
    {
        if (!$anew && $this->values !== null) {
            return $this->values;
        }
        $values = [];
        $fields = $this->getFields();
        foreach ($fields as $name => $field) {
            if ($field->isExclude()) {
                continue;
            }
            $field->joinData($values);
        }
        $this->values = $values;
        return $values;
    }

    /**
     * 设置值
     * @param array|int|null $data
     */
    public function setData(array|int|null $data = null)
    {
        if (is_int($data)) {
            try {
                $data = DB::getItem($this->table, $data);
            } catch (DBException $e) {
                Logger::error($e->getMessage(), $e->getMessage());
            }
        }
        if ($data == null) {
            return;
        }
        $fields = $this->getFields();
        foreach ($fields as $field) {
            if ($field->close) {
                continue;
            }
            $value = $field->fromData($data);
            $field->setValue($value);
        }
    }

    /**
     * 清除数据
     */
    public function clearData()
    {
        $this->values = null;
        $fields = $this->fields;
        foreach ($fields as $field) {
            $field->clearValue();
        }
    }

    /**
     * 自动完成表单
     * @param string $method
     * @return array
     */
    public function autoComplete(string $method = 'post'): array
    {
        $method = strtolower($method);
        $data = $_REQUEST;
        if ($method == 'get') {
            $data = $_GET;
        } elseif ($method == 'post') {
            $data = $_POST;
        }
        $this->fillComplete($data);
        return $this->getData();
    }


    /**
     * @param array $data
     */
    public function fillComplete(array $data)
    {
        $fields = $this->getFields();
        $valFnField = [];
        foreach ($fields as $field) {
            if ($field->close || ($field->offEdit && $this->type == 'edit')) {
                continue;
            }
            $valFunc = $field->valFunc;
            if (!empty($valFunc) && is_callable($valFunc)) {
                $valFnField[] = $field;
                continue;
            }
            if ($field->viewClose) {
                continue;
            }
            $value = $field->fromParam($data);
            $field->setValue($value);
        }
        //变量函数计算值
        foreach ($valFnField as $field) {
            $value = call_user_func($field->valFunc, $this);
            $field->setValue($value);
        }
    }

    /**
     * 验证函数
     * @param ?array $errors
     * @return bool
     */
    public function validate(?array &$errors = []): bool
    {
        $fields = $this->getFields();
        foreach ($fields as $field) {
            $field->validDynamic();
        }
        $result = true;
        if ($errors === null) {
            $errors = [];
        }
        foreach ($fields as $field) {
            if (!$field->validate($errors)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * 获取要显示的字段
     * @param string $tabIndex
     * @return Field[]
     */
    public function getViewFields(string $tabIndex = ''): array
    {
        $fields = $this->getFields($tabIndex);
        //修正显示
        $keys = array_keys($fields);
        $temp = [];
        for ($idx = 0, $len = count($keys); $idx < $len; $idx++) {
            $key = $keys[$idx];
            $field = $fields[$key];
            if (!$field->isView()) {
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
            if ($field->viewMerge == 0) {
                $temp[$key] = $field;
            }
        }
        foreach ($this->mergeForms as $form) {
            $temp = array_merge($temp, $form->getViewFields($tabIndex));
        }
        return $temp;
    }

    /**
     * 合并表单
     * @param Form $form
     * @param string $prefix
     */
    public function mergeView(Form $form, string $prefix = '')
    {
        if (!empty($prefix)) {
            $fields = $form->getFields();
            foreach ($fields as $child) {
                $child->setAttr('id', $prefix . '_' . $child->boxId);
                $child->setAttr('name', $prefix . '_' . $child->boxName);
                //如果存在拆分的时候
                if (property_exists($child, 'names') && isset($child->names) && is_array($child->names)) {
                    $names = $child->names;
                    foreach ($names as $nKey => $name) {
                        if (is_string($name)) {
                            $names[$nKey] = $prefix . '_' . $name;
                        }
                    }
                    $child->names = $names;
                }
                //修正动态数据
                if (!empty($child->dynamic)) {
                    $child->createDynamic();
                    $dataDynamic = $child->getAttr('data-dynamic');
                    if (!empty($dataDynamic)) {
                        foreach ($dataDynamic as &$item) {
                            foreach (['show', 'hide', 'off', 'on'] as $key) {
                                if (isset($item[$key])) {
                                    if (is_string($item[$key])) {
                                        $item[$key] = explode(',', $item[$key]);
                                    }
                                    if (is_array($item[$key])) {
                                        foreach ($item[$key] as $idx => $xit) {
                                            $item[$key][$idx] = $prefix . '_' . $xit;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $child->setAttr('data-dynamic', $dataDynamic);
                }
            }
        }
        $this->mergeForms[] = $form;
    }

}