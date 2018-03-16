<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 17:34
 */

namespace  beacon\widget;

use beacon\Field;

interface BoxInterface
{
    public function code(Field $field, $args);

    //从表单读数据
    public function assign(Field $field, array $data);

    //写入数据库的
    public function fill(Field $field, array &$values);

    //从数据库中读数据
    public function init(Field $field, array $values);
}