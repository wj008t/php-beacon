<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/4/18
 * Time: 15:30
 */

namespace beacon;


interface SqlSelectInterface
{
    public function where(string $sql, $args = null);

    public function search(string $sql, $value, $type = SqlCondition::WITHOUT_EMPTY, $format = null);

    public function limit(int $offset = 0, int $size = 0);

    public function createSql();

    public function getPageList($size = 20, $pagekey = 'page');

    public function getList();
}