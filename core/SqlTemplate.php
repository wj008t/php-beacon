<?php

namespace beacon;

use sdopx\Sdopx;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/5
 * Time: 1:37
 */
class SqlTemplate
{


    private $template = '';

    private $condition = null;

    private $limit = '';

    private $param = [];

    /**
     * SqlTemplate constructor.
     * @param $template
     * @param array $param
     */
    public function __construct($template, $param = [])
    {
        $this->template = $template;
        $this->param = $param;
        $this->condition = new SqlCondition();
    }

    public function where(string $sql, $args = null)
    {
        $this->condition->where($sql, $args);
        return $this;
    }

    public function search(string $sql, $value, $type = SqlCondition::WITHOUT_EMPTY, $format = null)
    {
        $this->condition->search($sql, $value, $type, $format);
        return $this;
    }

    public function limit(int $offset = 0, int $size = 0)
    {
        if ($offset === 0 && $size == 0) {
            return $this;
        }
        if ($size === 0) {
            $this->limit = 'limit ' . $offset;
        } else {
            $this->limit = 'limit ' . $offset . ',' . $size;
        }
        return $this;
    }

    public function createSql()
    {
        $whereSQL = '';
        if ($this->condition !== null) {
            $where = [];
            $args = [];
            $frame = $this->condition->getFrame();
            if (!empty($frame['sql'])) {
                if (preg_match('@^(AND|OR)\s+@i', $frame['sql'])) {
                    $where[] = $frame['sql'];
                } else {
                    $where[] = 'AND (' . $frame['sql'] . ')';
                }
            }
            if ($frame['args'] !== null && is_array($frame['args'])) {
                $args = $frame['args'];
            }
            $whereSQL = Mysql::format(join(' ', $where), $args);
        }
        $sql = Sdopx::fetchTemplate('string:' . $this->template, ['where' => $whereSQL, 'param' => $this->param], 'sql');
        return $sql;
    }

    public function getPageList($size = 20, $pagekey = 'page')
    {
        $sql = $this->createSql();
        return new PageList($sql, null, $size, $pagekey);
    }

    public function getList()
    {
        $sql = $this->createSql();
        return DB::getList($sql);
    }

}