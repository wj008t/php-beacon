<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/20
 * Time: 0:40
 */

namespace beacon\widget;

use beacon\Field;
use beacon\Form;
use beacon\Request;
use beacon\Utils;
use beacon\View;


class Container extends Hidden
{
    /**
     * 完善字段信息
     * @param Field $field
     * @param Form $form
     * @param string $index
     */
    public static function perfect(Field $field, Form $form, $index = '@@index@@')
    {
        $fields = $form->getFields();
        foreach ($fields as $name => $child) {
            if ($field->dataValDisabled) {
                $child->dataValDisabled = true;
            }
            //当个
            if ($field->mode == 'single') {
                $child->boxId = $field->boxId . '_' . $child->boxId;
                $child->boxName = $field->boxName . '[' . $child->boxName . ']';
                $child->offEdit = $child->offEdit || $field->offEdit;
                //如果存在拆分的时候
                if ($child->names && is_array($child->names)) {
                    $names = $child->names;
                    foreach ($names as $nKey => $nItem) {
                        if (is_array($nItem) && $nItem['field']) {
                            $nItem['field'] = $field->boxName . '[' . $nItem['field'] . ']';
                        } elseif (is_string($nItem)) {
                            $nItem = $field->boxName . '[' . $nItem . ']';
                        }
                        $names[$nKey] = $nItem;
                    }
                    $child->names = $names;
                }
                //修正动态数据
                if (!empty($child->dynamic)) {
                    $form->createDynamic($child);
                    $dataDynamic = $child->dataDynamic;
                    foreach ($dataDynamic as &$item) {
                        foreach (['show', 'hide', 'off', 'on'] as $key) {
                            if (isset($item[$key])) {
                                if (is_string($item[$key])) {
                                    $item[$key] = explode(',', $item[$key]);
                                }
                                if (is_array($item[$key])) {
                                    foreach ($item[$key] as $idx => $xit) {
                                        $item[$key][$idx] = $field->boxId . '_' . $xit;
                                    }
                                }
                            }
                        }
                    }
                    $child->dataDynamic = $dataDynamic;
                }

            } //多组
            else if ($field->mode == 'multiple') {
                $child->boxId = $field->boxId . '_' . $index . '_' . $child->boxId;
                $child->boxName = $field->boxName . '[' . $index . '][' . $child->boxName . ']';
                $child->offEdit = $child->offEdit || $field->offEdit;
                //如果存在拆分的时候
                if ($child->names && is_array($child->names)) {
                    $names = $child->names;
                    foreach ($names as $nKey => $nItem) {
                        if (is_array($nItem) && $nItem['field']) {
                            $nItem['field'] = $field->boxName . '[' . $index . '][' . $nItem['field'] . ']';
                        } elseif (is_string($nItem)) {
                            $nItem = $field->boxName . '[' . $index . '][' . $nItem . ']';
                        }
                        $names[$nKey] = $nItem;
                    }
                    $child->names = $names;
                }
                if (!empty($child->dynamic)) {
                    $form->createDynamic($child);
                    $dataDynamic = $child->dataDynamic;
                    foreach ($dataDynamic as &$item) {
                        foreach (['show', 'hide', 'off', 'on'] as $key) {
                            if (isset($item[$key])) {
                                if (is_string($item[$key])) {
                                    $item[$key] = explode(',', $item[$key]);
                                }
                                if (is_array($item[$key])) {
                                    foreach ($item[$key] as $idx => $xit) {
                                        $item[$key][$idx] = $field->boxId . '_' . $index . '_' . $xit;
                                    }
                                }
                            }
                        }
                    }
                    $child->dataDynamic = $dataDynamic;
                }
            }
            $child->dataValOutput = '#' . $field->boxId . '-validation';
        }
    }

    /**
     * 获取插件表单
     * @param Field $field
     * @param string|null $type
     * @return Form
     * @throws \Exception
     */
    public static function plugForm(Field $field, string $type = null)
    {
        $className = $field->plugName;
        if (empty($className) || !class_exists($className)) {
            throw new \Exception('没有找到插件的类 ' . $className);
        }
        if ($type) {
            return new $className($type);
        }
        return new $className($field->getForm()->getType());
    }

    /**
     * 生成代码
     * @param Field $field
     * @param array $attr
     * @return string
     * @throws \Exception
     */
    public function code(Field $field, $attr = [])
    {
        $field->mode = empty($field->mode) ? 'single' : $field->mode;
        #单一模式
        if ($field->mode == 'single') {
            $attr['type'] = '';
            $attr['name'] = '';
            $attr['value'] = '';
            $attr['class'] = '';
            $attr['id'] = 'row_' . $field->boxId;
            $attr = WidgetHelper::mergeAttributes($field, $attr);
            if (empty($field->plugName)) {
                return '<div ' . join(' ', $attr) . '></div>';
            }
            $form = self::plugForm($field);
            if (empty($form->template)) {
                throw new \Exception('插件模板不存在');
            }
            $viewer = new View();
            $viewer->fetch($form->template);
            $wrapFunc = $viewer->getHook('single');
            if ($wrapFunc == null) {
                throw new \Exception('模板中没有找到 {hook fn="single"} 的钩子函数');
            }
            $tinker = $field->getFunc('tinker');
            if ($tinker && is_callable($tinker)) {
                call_user_func($tinker, $form);
            }
            $form->setValues($field->value);
            self::perfect($field, $form);
            $code = $wrapFunc(['field' => $field, 'form' => $form]);
            return '<div ' . join(' ', $attr) . '>' . $code . '</div>';
        } else {
            #多增模式
            if (empty($field->plugName)) {
                return '';
            }
            $form = self::plugForm($field, 'add');
            if (empty($form->template)) {
                throw new \Exception('插件模板不存在');
            }
            $viewer = new View();
            $viewer->fetch($form->template);
            $wrapFunc = $viewer->getHook('multiple-wrap');
            $itemFunc = $viewer->getHook('multiple-item');
            if ($wrapFunc == null) {
                throw new \Exception('模板中没有找到 {hook fn="multiple-wrap"} 的钩子函数');
            }
            if ($itemFunc == null) {
                throw new \Exception('模板中没有找到 {hook fn="multiple-item"} 的钩子函数');
            }
            $out = [];
            $values = $field->value;
            $index = 0;
            $tinker = $field->getFunc('tinker');
            if (!empty($values) && is_array($values)) {
                foreach ($values as $idx => $item) {
                    $pForm = self::plugForm($field, 'edit');
                    if ($tinker && is_callable($tinker)) {
                        call_user_func($tinker, $pForm);
                    }
                    $pForm->setValues($item);
                    self::perfect($field, $pForm, $index);
                    $code = $itemFunc(['field' => $field, 'form' => $pForm, 'index' => 'a' . $index]);
                    $out[] = $code;
                    $index++;
                }
            }
            if ($tinker && is_callable($tinker)) {
                call_user_func($tinker, $form);
            }
            self::perfect($field, $form);
            $code = $itemFunc(['field' => $field, 'form' => $form, 'index' => '@@index@@']);
            $data = [];
            $data[] = 'data-index="' . htmlspecialchars($index) . '"';
            if ($field->dataMinSize) {
                $data[] = 'data-min-size="' . $field->dataMinSize . '"';
            }
            if ($field->dataMaxSize) {
                $data[] = 'data-max-size="' . $field->dataMaxSize . '"';
            }
            if ($field->dataInitSize) {
                $data[] = 'data-init-size="' . $field->dataInitSize . '"';
            }
            return $wrapFunc([
                'field' => $field,
                'form' => $form,
                'body' => join('', $out),
                'source' => base64_encode($code),
                'lastIndex' => $index,
                'attrs' => join(' ', $data)
            ]);
        }
    }

    /**
     * @param Field $field
     * @param array $input
     * @return array|null
     * @throws \Exception
     */
    public function assign(Field $field, array $input)
    {
        $field->mode = empty($field->mode) ? 'single' : $field->mode;
        $boxName = $field->boxName;
        $itemData = Request::input($input, $boxName . ':a', $field->default);
        if (empty($field->plugName)) {
            return $field->value;
        }
        //单个
        if ($field->mode == 'single') {
            $form = self::plugForm($field);
            $tinker = $field->getFunc('tinker');
            if ($tinker && is_callable($tinker)) {
                call_user_func($tinker, $form);
            }
            $childValue = $form->fillComplete($itemData);
            if ($field->dataValDisabled !== true) {
                if (!$form->validation($errors)) {
                    $childError = [];
                    foreach ($errors as $key => $err) {
                        $boxName = $field->boxName . '[' . $key . ']';
                        $childError[$boxName] = $err;
                    }
                    $field->childError = $childError;
                }
            }
            $field->value = $childValue;
            return $field->value;
        }
        //多个
        if ($field->mode == 'multiple') {
            $temp = [];
            $childError = [];
            foreach ($itemData as $idx => $item) {
                $form = self::plugForm($field);
                $tinker = $field->getFunc('tinker');
                if ($tinker && is_callable($tinker)) {
                    call_user_func($tinker, $form);
                }
                $childValue = $form->fillComplete($item);
                if ($field->dataValDisabled !== true) {
                    $errors = [];
                    if (!$form->validation($errors)) {
                        foreach ($errors as $key => $err) {
                            $boxName = $field->boxName . '[' . $idx . '][' . $key . ']';
                            $childError[$boxName] = $err;
                        }
                    }
                }
                $temp[] = $childValue;
            }
            $field->childError = $childError;
            $field->value = $temp;
            return $field->value;
        }
    }

    public function fill(Field $field, array &$values)
    {
        $values[$field->name] = json_encode($field->value, JSON_UNESCAPED_UNICODE);
    }

    public function init(Field $field, array $values)
    {
        $temp = isset($values[$field->name]) ? $values[$field->name] : null;
        $value = null;
        if (is_array($temp)) {
            $value = $temp;
        } else if (is_string($temp) && Utils::isJson($temp)) {
            $value = json_decode($temp, true);
        }
        $field->value = $value;
    }


}

