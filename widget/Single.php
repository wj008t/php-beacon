<?php


namespace beacon\widget;


use beacon\core\Field;
use beacon\core\Form;
use beacon\core\Logger;
use beacon\core\Request;
use beacon\core\Util;
use beacon\core\View;


#[\Attribute]
/**
 * 单行容器
 */
class Single extends Field
{
    public string $varType = 'array';
    /**
     * @var string 部件类名称
     */
    public string $itemClass = '';
    /**
     * 子表单类型
     * @var string
     */
    public string $subType = 'add';
    /**
     * 缓存设置数据
     * @var array
     */
    protected array $cacheData = [];

    /**
     * 子表单错误
     * @var array
     */
    public array $childError = [];
    /**
     * 用于修正表单的方法
     * @var string|array|null
     */
    public string|array|\Closure|null $modifyFunc = null;

    /**
     * 指定模板
     * @var string
     */
    public string $template = '';

    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['itemClass']) && is_string($args['itemClass'])) {
            $this->itemClass = $args['itemClass'];
        }
        if (isset($args['subType']) && is_string($args['subType'])) {
            $this->subType = $args['subType'];
        }
        if (isset($args['modifyFunc']) && is_callable($args['modifyFunc'])) {
            $this->modifyFunc = $args['modifyFunc'];
        }
        if (isset($args['template']) && is_string($args['template'])) {
            $this->template = $args['template'];
        }
    }


    public static function perfect(array $fields, string $boxName, string $boxId = '')
    {
        if (empty($boxId)) {
            $boxId = $boxName;
        }
        foreach ($fields as $child) {
            $child->setAttr('id', $boxId . '_' . $child->boxId);
            $child->setAttr('name', $boxName . '[' . $child->boxName . ']');
            //如果存在拆分的时候
            if (property_exists($child, 'names') && isset($child->names) && is_array($child->names)) {
                $names = $child->names;
                foreach ($names as $nKey => $name) {
                    if (is_string($name)) {
                        $names[$nKey] = $boxName . '[' . $name . ']';
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
                                        $item[$key][$idx] = $boxId . '_' . $xit;
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

    /**
     * 完善表单信息
     * @param Form $subForm
     */
    public function perfectForm(Form $subForm)
    {
        $boxId = $this->boxId;
        $boxName = $this->boxName;
        $fields = $subForm->getFields();
        self::perfect($fields, $boxName, $boxId);
        foreach ($fields as $child) {
            if (isset($this->valid['disabled']) && $this->valid['disabled']) {
                $child->valid['disabled'] = true;
            }
            $child->offEdit = $child->offEdit || $this->offEdit;
            if (isset($this->valid['merge']) && $this->valid['merge'] === true && empty($child->valid['display'])) {
                if (!empty($this->valid['display'])) {
                    $child->valid['display'] = $this->valid['display'];
                } else {
                    $child->valid['display'] = '#' . $boxId . '-validation';
                }
            }
        }

    }

    /**
     * 获取插件
     * @param string|object $object
     * @param string $type
     * @return Form|null
     * @throws \Exception
     */
    private function getSubForm(string|object $object, string $type = ''): ?Form
    {
        if (empty($type)) {
            $form = $this->getForm();
            if (empty($form)) {
                $type = $this->subType;
            } else {
                $type = $form->getType();
            }
        }
        $subForm = Form::create($object, $type);
        if ($subForm == null) {
            throw new \Exception('子表单获取失败');
        }
        if (!empty($this->modifyFunc) && is_callable($this->modifyFunc)) {
            call_user_func($this->modifyFunc, $subForm);
        }
        return $subForm;
    }

    /**
     * 获取类名
     * @return string
     */
    private function getClassName(): string
    {
        if (!empty($this->itemClass) && class_exists($this->itemClass)) {
            return $this->itemClass;
        }
        $typeName = $this->varType;
        $temp = explode('|', $typeName);
        foreach ($temp as $item) {
            $item = ltrim($item, '?');
            if (preg_match('@^[a-z]+$@', $item)) {
                continue;
            }
            if (class_exists($item)) {
                return $item;
            }
        }
        return '';
    }

    /**
     * @param array $attrs
     * @return string
     * @throws \Exception
     */
    protected function code(array $attrs = []): string
    {
        $className = $this->getClassName();
        $attrs['id'] = 'single-' . $this->boxId;
        if (empty($className)) {
            return static::makeTag('div', ['attrs' => $attrs, 'exclude' => ['name', 'value']]);
        }
        $subForm = $this->getSubForm($className);
        if (empty($subForm->template) && empty($this->template)) {
            throw new \Exception('子表单获模板未定义');
        }
        $subForm->setData($this->cacheData);
        $this->perfectForm($subForm);
        $viewer = new View();
        $viewer->assign('field', $this);
        $viewer->assign('form', $subForm);
        $template = $subForm->template;
        if (!empty($this->template)) {
            $template = $this->template;
        }
        $code = $viewer->fetch($template);
        return static::makeTag('div', ['attrs' => $attrs, 'exclude' => ['name', 'value'], 'code' => $code]);
    }

    /**
     * @param array $param
     * @return mixed
     * @throws \Exception
     */
    public function fromParam(array $param = []): mixed
    {
        $boxName = $this->boxName;
        $itemData = Request::lookType($param, $boxName, 'array', []);
        $className = $this->getClassName();
        if (empty($className)) {
            return $itemData;
        }
        $object = new $className();
        $subForm = $this->getSubForm($object);
        $subForm->fillComplete($itemData);
        $validDisabled = isset($this->valid['disabled']) && $this->valid['disabled'];
        if (!$validDisabled) {
            $this->childError = [];
            if (!$subForm->validate($errors)) {
                foreach ($errors as $key => $error) {
                    $childName = $boxName . '[' . $key . ']';
                    $this->childError[$childName] = $error;
                }
            }
        }
        $this->cacheData = $subForm->getData();
        if ($this->varType == 'array') {
            return $this->cacheData;
        }
        return $object;
    }

    /**
     * @param array $data
     * @return ?object
     * @throws \Exception
     */
    public function setData(array $data): ?object
    {
        $className = $this->getClassName();
        if (empty($className)) {
            return null;
        }
        $this->cacheData = $data;
        $object = new $className();
        $subForm = $this->getSubForm($object);
        $subForm->setData($this->cacheData);
        return $object;
    }

    /**
     * 获取数据值
     * @return array
     */
    public function getData(): array
    {
        return $this->cacheData;
    }

    /**
     * @param array $data
     * @return object|array|null
     * @throws \Exception
     */
    public function fromData(array $data = []): object|null|array
    {
        $itemData = isset($data[$this->name]) ? $data[$this->name] : null;
        if (is_string($itemData) && Util::isJson($itemData)) {
            $itemData = json_decode($itemData, true);
        }
        if (!is_array($itemData)) {
            $itemData = [];
        }
        $object = $this->setData($itemData);
        if ($this->varType == 'array') {
            return $this->cacheData;
        }
        return $object;
    }


    /**
     * 验证数据
     * @param array $errors
     * @return bool
     */
    public function validate(array &$errors): bool
    {
        foreach ($this->childError as $childName => $error) {
            if (!empty($error)) {
                $errors[$childName] = $error;
                if (empty($field->error)) {
                    $this->error = $error;
                }
                return false;
            }
        }
        return parent::validate($errors);
    }
}