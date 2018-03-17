<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/20
 * Time: 0:40
 */

namespace beacon\widget;


use beacon\DB;
use beacon\Field;
use beacon\Form;
use beacon\Request;
use beacon\Route;
use beacon\Utils;
use beacon\View;


class Plugin extends Hidden
{
    public static function correct(Field $field, Form $form, $index = '@@index@@')
    {
        $fields = $form->getTabFields();
        foreach ($fields as $name => $child) {
            if ($field->dataValOff) {
                $child->dataValOff = true;
            }
            if ($field->plugMode == 'simple') {
                $child->boxId = $field->boxId . '_' . $child->boxId;
                $child->boxName = $field->boxName . '[' . $child->boxName . ']';

                if ($child->dynamic && (!isset($child->dataDynamic) || empty($child->dataDynamic))) {
                    $form->createDynamic($child);
                    $dataDynamic = $child->dataDynamic;
                    foreach ($child->dataDynamic as &$item) {
                        foreach (['show', 'hide', 'val-off', 'val-on'] as $key) {
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
            else {
                $child->boxId = $field->boxId . '_' . $index . '_' . $child->boxId;
                $child->boxName = $field->boxName . '[' . $index . '][' . $child->boxName . ']';

                if ($child->dynamic && (!isset($child->dataDynamic) || empty($child->dataDynamic))) {
                    $form->createDynamic($child);
                    $dataDynamic = $child->dataDynamic;
                    foreach ($dataDynamic as &$item) {
                        foreach (['show', 'hide', 'val-off', 'val-on'] as $key) {
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

            if ($field->plugType == 1 || $field->plugType == 2 || $field->plugType == 4 || $field->plugType == 5) {
                $child->dataValFor = '#' . $field->boxId . '-validation';
            }
            if (($field->plugType == 2 || $field->plugType == 5) && empty($child->boxPlaceholder)) {
                $child->boxPlaceholder = $child->label;
                if ($child->type == 'check') {
                    $child->afterText = $child->label;
                }
            }
        }
    }

    public static function getTemplate()
    {
        $sdopx = View::newInstance();
        $common_dir = Utils::path(ROOT_DIR, 'view/widget');
        $sdopx->addTemplateDir($common_dir);
        return $sdopx;
    }


    public static function getFormInstance(Field $field, string $type = null)
    {
        if (empty($field->plugName)) {
            throw new \Exception('插件模块名称没有填写');
        }
        if (preg_match('@\\\\@', $field->plugName)) {
            $class = $field->plugName;
        } else {
            $class = Route::getNamespace() . '\\form\\' . $field->plugName;
        }
        if (!class_exists($class)) {
            throw new \Exception('没有找出插件的类 ' . $class);
        }
        if ($type) {
            return new $class($type);
        }
        return new $class($field->getForm()->getType());
    }

    public function code(Field $field, $args)
    {
        $field->plugType = isset($field->plugType) ? $field->plugType : 0;
        $field->plugMode = isset($field->plugMode) && $field->plugMode == 'composite' ? 'composite' : 'simple';
        if (empty($field->viewtplName)) {
            if ($field->plugMode == 'simple') {
                $field->viewtplName = 'plugin_simple' . $field->plugType . '.tpl';
            } else {
                $field->viewtplName = 'plugin_composite' . $field->plugType . '.tpl';
            }
        }
        if ($field->plugMode == 'simple') {
            $form = self::getFormInstance($field);
            $form->initValues($field->value);
            self::correct($field, $form);
            $viewer = self::getTemplate();
            $viewer->assign('form', $form);
            $viewer->assign('field', $field);
            if (isset($form->viewtplName) && !empty($form->viewtplName)) {
                return $viewer->fetch($form->viewtplName);
            }
            return $viewer->fetch($field->viewtplName);
        } else {
            $form = self::getFormInstance($field, 'add');
            $viewer = self::getTemplate();
            if (isset($form->viewtplName) && !empty($form->viewtplName)) {
                $viewer->fetch($form->viewtplName);
            } else {
                $viewer->fetch($field->viewtplName);
            }
            $layerFunc = $viewer->getHack('plugin-layer');
            $itemFunc = $viewer->getHack('plugin-item');
            $out = [];
            $values = $field->value;
            $index = 0;
            if (!empty($values) && is_array($values)) {
                foreach ($values as $idx => $item) {
                    $xform = self::getFormInstance($field);
                    $xform->initValues($item);
                    if ($field->autoSave && !empty($field->referenceField) && isset($item['id'])) {
                        $xname = $field->boxName . '[a' . $index . '][id]';
                        $xform->addHideBox($xname, $item['id']);
                    }
                    self::correct($field, $xform, 'a' . $index);
                    $out[] = $itemFunc(['field' => $field, 'form' => $xform, 'index' => 'a' . $index]);
                    $index++;
                }
            }
            self::correct($field, $form);
            $code = $itemFunc(['field' => $field, 'form' => $form, 'index' => '@@index@@']);
            return $layerFunc(['field' => $field, 'form' => $form, 'content' => join('', $out), 'code' => base64_encode($code), 'lastIndex' => $index]);
        }
    }

    public function assign(Field $field, array $data)
    {
        $field->plugMode = isset($field->plugMode) && $field->plugMode == 'composite' ? 'composite' : 'simple';
        $boxName = $field->boxName;
        $request = Request::instance();
        $itemData = $request->input($data, $boxName . ':a', $field->default);
        if (empty($field->plugName)) {
            return $field->value;
        }

        if ($field->plugMode == 'simple') {
            $form = self::getFormInstance($field);
            $form->fillComplete($itemData);
            if (!$form->validation($errors)) {
                $childError = [];
                foreach ($errors as $key => $err) {
                    $boxName = $field->boxName . '[' . $key . ']';
                    $childError[$boxName] = $err;
                }
                $field->childError = $childError;
            }
            $vdata = $form->getValues();
            $field->value = $vdata;
            return $field->value;
        }

        //复杂的==
        $temp = [];
        $childError = [];
        foreach ($itemData as $idx => $item) {
            $form = self::getFormInstance($field);
            $vdata = $form->fillComplete($item);
            if (!$form->validation($errors)) {
                foreach ($errors as $key => $err) {
                    $cboxName = $field->boxName . '[' . $idx . '][' . $key . ']';
                    $childError[$cboxName] = $err;
                }
            }
            if ($field->autoSave && !empty($field->referenceField) && isset($item['id'])) {
                $vdata['id'] = $item['id'];
            }

            $temp[] = $vdata;
        }

        $field->childError = $childError;
        $field->value = $temp;
        return $field->value;

    }

    public function fill(Field $field, array &$values)
    {
        if (!$field->autoSave || empty($field->referenceField)) {
            $values[$field->name] = json_encode($field->value, JSON_UNESCAPED_UNICODE);
        }
    }

    public function init(Field $field, array $values)
    {
        $temp = isset($values[$field->name]) ? $values[$field->name] : null;
        if (is_array($temp)) {
            $field->value = $temp;
        } else if (is_string($temp) && Utils::isJsonString($temp)) {
            $field->value = json_decode($temp, true);
        } else {
            $field->value = null;
        }
    }

    public static function getData(Field $field, int $id = 0)
    {
        $form = self::getFormInstance($field);
        if ($form == null || empty($form->tbname)) {
            return null;
        }
        $field->plugMode = isset($field->plugMode) && $field->plugMode == 'composite' ? 'composite' : 'simple';
        if ($field->plugMode == 'simple') {
            $row = DB::getRow('select * from `' . $form->tbname . '` where `' . $field->referenceField . '`=?', $id);
            return $row;
        } else if ($field->plugMode == 'composite') {
            if (!DB::existsField($form->tbname, 'nSort')) {
                DB::addField($form->tbname, 'nSort', [
                    'type' => 'int',
                    'len' => 11,
                    'comment' => '排序',
                ]);
            }
            $list = DB::getList('select * from `' . $form->tbname . '` where `' . $field->referenceField . '`=? order by nSort asc,id asc', $id);
            return $list;
        }
        return null;
    }

    public static function insert(Field $field, int $id = 0)
    {

        $form = self::getFormInstance($field);
        if ($form == null || empty($form->tbname)) {
            return;
        }
        $field->plugMode = isset($field->plugMode) && $field->plugMode == 'composite' ? 'composite' : 'simple';
        if ($field->plugMode == 'simple') {
            $childVal = $field->value;
            if (empty($childVal) || !is_array($childVal)) {
                return;
            }
            $childVal[$field->referenceField] = $id;
            DB::insert($form->tbname, $childVal);
            return;
        }
        if ($field->plugMode == 'composite') {
            $childList = $field->value;
            if (empty($childList) || !is_array($childList)) {
                return;
            }
            foreach ($childList as $childVal) {
                if (empty($childVal) || !is_array($childVal)) {
                    continue;
                }
                unset($childVal['id']);
                $childVal[$field->referenceField] = $id;
                DB::insert($form->tbname, $childVal);
            }
        }
    }

    public static function update(Field $field, int $id = 0)
    {
        $form = self::getFormInstance($field);
        if ($form == null || empty($form->tbname)) {
            return;
        }
        $field->plugMode = isset($field->plugMode) && $field->plugMode == 'composite' ? 'composite' : 'simple';
        //单个更新插入
        if ($field->plugMode == 'simple') {
            $childVal = $field->value;
            if (empty($childVal) || !is_array($childVal)) {
                return;
            }
            if (isset($childVal['id'])) {
                DB::update($form->tbname, $childVal, "`{$field->referenceField}`=? and id=?", [$id, $childVal['id']]);
            } else {
                DB::update($form->tbname, $childVal, "`{$field->referenceField}`=?", $id);
            }
            return;
        }
        //多个更新插入
        if ($field->plugMode == 'composite') {
            $childList = $field->value;
            if (empty($childList) || !is_array($childList)) {
                return;
            }
            $list = DB::getList("select id from `{$form->tbname}` where `{$field->referenceField}`=?", $id);
            $hasMaps = [];
            foreach ($list as $del) {
                $delId = $del['id'];
                $hasMaps[$delId] = $delId;
            }
            if (!DB::existsField($form->tbname, 'nSort')) {
                DB::addField($form->tbname, 'nSort', [
                    'type' => 'int',
                    'len' => 11,
                    'comment' => '排序',
                ]);
            }
            //批量添加条目
            foreach ($childList as $sort => $childVal) {
                if (empty($childVal) || !is_array($childVal)) {
                    continue;
                }
                $childVal['nSort'] = $sort;
                if (!empty($childVal['id']) && isset($hasMaps[$childVal['id']])) {
                    $oldId = $childVal['id'];
                    DB::update($form->tbname, $childVal, "`{$field->referenceField}`=? and id=?", [$id, $childVal['id']]);
                    unset($hasMaps[$oldId]);
                } else {
                    unset($childVal['id']);
                    $childVal[$field->referenceField] = $id;
                    DB::insert($form->tbname, $childVal);
                }
            }
            foreach ($hasMaps as $delId) {
                DB::delete($form->tbname, "`{$field->referenceField}`=? and id=?", [$id, $delId]);
            }
        }
    }

    public static function delete(Field $field, int $id = 0)
    {
        $form = self::getFormInstance($field);
        if ($form == null || empty($form->tbname)) {
            return;
        }
        DB::delete($form->tbname, "`{$field->referenceField}`=?", $id);
    }

}

