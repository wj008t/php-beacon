<?php


namespace beacon\widget;


use beacon\core\Field;
use beacon\core\Form;
use beacon\core\Request;
use beacon\core\Util;
use beacon\core\View;
use sdopx\lib\Raw;

#[\Attribute]
class Container extends Field
{
    protected array $_attrs=[
        'class'=>'container',
    ];
    /**
     * @var string 部件类名称
     */
    public string $itemClass = '';

    /**
     * @var string 每项类型
     */
    public string $itemType = 'array';

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
     * @var string|array|\Closure|null
     */
    public string|array|\Closure|null $modifyFunc = null;

    /**
     * 指定模板
     * @var string
     */
    public string $template = '';

    /**
     * 指定模板
     * @var bool
     */
    public bool $removeBtn = true;
    /**
     * 插入按钮
     * @var bool
     */
    public bool $insertBtn = false;
    /**
     * 排序按钮
     * @var bool
     */
    public bool $sortBtn = false;

    public int $minSize = 0;
    public int $maxSize = 1000;
    public int $initSize = 0;

    /**
     * Container constructor.
     * @param array $args
     */
    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['itemClass']) && is_string($args['itemClass'])) {
            $this->itemClass = $args['itemClass'];
        }
        if (isset($args['itemType']) && is_string($args['itemType'])) {
            $this->itemType = $args['itemType'];
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
        if (isset($args['removeBtn']) && is_bool($args['removeBtn'])) {
            $this->removeBtn = $args['removeBtn'];
        }
        if (isset($args['insertBtn']) && is_bool($args['insertBtn'])) {
            $this->insertBtn = $args['insertBtn'];
        }
        if (isset($args['sortBtn']) && is_bool($args['sortBtn'])) {
            $this->sortBtn = $args['sortBtn'];
        }
        if (isset($args['minSize']) && is_int($args['minSize'])) {
            $this->minSize = $args['minSize'];
        }
        if (isset($args['maxSize']) && is_int($args['maxSize'])) {
            $this->maxSize = $args['maxSize'];
        }
        if (isset($args['initSize']) && is_int($args['initSize'])) {
            $this->initSize = $args['initSize'];
        }
    }

    /**
     * 完善表单嘻嘻
     * @param Form $subForm
     * @param string $index
     */
    private function perfectForm(Form $subForm, string $index = '@@index@@')
    {
        $fields = $subForm->getFields();
        foreach ($fields as $child) {
            if (isset($this->valid['disabled']) && $this->valid['disabled']) {
                $child->valid['disabled'] = true;
            }
            $boxId = $this->boxId;
            $boxName = $this->boxName;
            $child->setAttr('id', $boxId . '_' . $index . '_' . $child->boxId);
            $child->setAttr('name', $boxName . '[' . $index . '][' . $child->boxName . ']');
            $child->offEdit = $child->offEdit || $this->offEdit;
            //如果存在拆分的时候
            if (property_exists($child, 'names') && isset($child->names) && is_array($child->names)) {
                $names = $child->names;
                foreach ($names as $nKey => $name) {
                    if (is_string($name)) {
                        $names[$nKey] = $boxName . '[' . $index . '][' . $name . ']';
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
                                        $item[$key][$idx] = $boxId . '_' . $index . '_' . $xit;
                                    }
                                }
                            }
                        }
                    }
                }
                $child->setAttr('data-dynamic', $dataDynamic);
            }
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
     * 获取类名
     * @return string
     */
    private function getClassName(): string
    {
        if (!empty($this->itemClass) && class_exists($this->itemClass)) {
            return $this->itemClass;
        }
        $typeName = $this->itemType;
        if ($typeName == 'array') {
            return '';
        }
        if (!empty($typeName) && class_exists($typeName)) {
            return $typeName;
        }
        return '';
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
     * @param array $attrs
     * @return string
     * @throws \Exception
     */
    protected function code(array $attrs = []): string
    {
        $className = $this->getClassName();
        if (empty($className)) {
            return '';
        }
        $subForm = $this->getSubForm($className, 'add');
        if (empty($subForm->template) && empty($this->template)) {
            throw new \Exception('子表单获模板未定义');
        }

        $viewer = new View();
        $template = $subForm->template;
        if (!empty($this->template)) {
            $template = $this->template;
        }
        $viewer->fetch($template);
        $wrapFunc = $viewer->getHook('wrap');
        $itemFunc = $viewer->getHook('item');
        $mode = 'div';
        if ($wrapFunc == null) {
            $wrapFunc = $viewer->getHook('table_wrap');
            $mode = 'table';
        }
        if ($wrapFunc == null) {
            throw new \Exception('模板中没有找到 {hook fn="wrap"} 的钩子函数');
        }
        if ($itemFunc == null) {
            throw new \Exception('模板中没有找到 {hook fn="item"} 的钩子函数');
        }
        $code = [];
        $index = 0;
        $values = $this->cacheData;
        if (!empty($values) && is_array($values)) {
            foreach ($values as $item) {
                $editForm = $this->getSubForm($className, 'add');
                $editForm->setData($item);
                $this->perfectForm($editForm, $index);
                if ($mode == 'table') {
                    $subCode = '<tr class="container-item">' . $itemFunc(['field' => $this, 'form' => $editForm, 'index' => 'a' . $index]) . '</tr>';
                } else {
                    $subCode = '<div class="container-item">' . $itemFunc(['field' => $this, 'form' => $editForm, 'index' => 'a' . $index]) . '</div>';
                }
                $code[] = $subCode;
                $index++;
            }
        }
        $this->perfectForm($subForm);
        if ($mode == 'table') {
            $source = '<tr class="container-item">' . $itemFunc(['field' => $this, 'form' => $subForm, 'index' => '@@index@@']) . '</tr>';
        } else {
            $source = '<div class="container-item">' . $itemFunc(['field' => $this, 'form' => $subForm, 'index' => '@@index@@']) . '</div>';
        }
        $attrs['yee-module'] = $this->getYeeModule('container');
        $attrs['data-index'] = $index;
        $attrs['data-min-size'] = $this->minSize;
        $attrs['data-max-size'] = $this->maxSize;
        $attrs['data-init-size'] = $this->initSize;
        $attrs['data-source'] = base64_encode($source);
        $wrapStyle = '';
        if (!empty($attrs['wrap-style'])) {
            $wrapStyle = ' style="' . $attrs['wrap-style'] . '"';
            unset($attrs['wrap-style']);
        }
        $data = [];
        $data['field'] = $this;
        if ($mode == 'table') {
            $data['body'] = new Raw('<tbody class="container-wrap"' . $wrapStyle . '>' . join('', $code) . '</tbody>');
        } else {
            $data['body'] = new Raw('<div class="container-wrap"' . $wrapStyle . '>' . join('', $code) . '</div>');
        }
        unset($attrs['value']);
        return static::makeTag('div', ['attrs' => $attrs, 'exclude' => ['name'], 'code' => $wrapFunc($data)]);
    }

    /**
     * @param array $param
     * @return mixed
     * @throws \Exception
     */
    public function fromParam(array $param = []): array
    {
        $boxName = $this->boxName;
        $itemData = Request::lookType($param, $boxName, 'array', []);
        $className = $this->getClassName();
        if (empty($className)) {
            return $itemData;
        }
        $this->childError = [];
        $this->cacheData = [];
        $objects = [];
        foreach ($itemData as $idx => $datum) {
            if (!is_array($datum)) {
                continue;
            }
            $object = new $className();
            $subForm = $this->getSubForm($object);
            $subForm->fillComplete($datum);
            $validDisabled = isset($this->valid['disabled']) && $this->valid['disabled'];
            if (!$validDisabled) {
                if (!$subForm->validate($errors)) {
                    foreach ($errors as $key => $error) {
                        $childName = $boxName . '[' . $idx . '][' . $key . ']';
                        $this->childError[$childName] = $error;
                    }
                }
            }
            $objects[] = $object;
            $this->cacheData[] = $subForm->getData();
        }
        if ($this->itemType == 'array') {
            return $this->cacheData;
        }
        return $objects;
    }


    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function setData(array $data): array
    {
        $this->cacheData = $data;
        if ($this->itemType == 'array') {
            return $this->cacheData;
        }
        $className = $this->getClassName();
        if (empty($className)) {
            return [];
        }
        $objects = [];
        foreach ($data as $datum) {
            $object = new $className();
            $subForm = $this->getSubForm($object);
            $subForm->setData($datum);
            $objects[] = $object;
        }
        return $objects;
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
     * @return array
     * @throws \Exception
     */
    public function fromData(array $data = []): array
    {
        $itemData = $data[$this->name] ?? null;
        if (is_string($itemData) && Util::isJson($itemData)) {
            $itemData = json_decode($itemData, true);
        }
        if (!is_array($itemData)) {
            $itemData = [];
        }
        return $this->setData($itemData);
    }


    /**
     * 验证数据
     * @param array $errors
     * @return bool
     */
    public function validate(array &$errors): bool
    {
        if(isset($this->valid['disabled']) && $this->valid['disabled']){
            return parent::validate($errors);
        }
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