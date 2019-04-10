<?php

namespace beacon;

use sdopx\Sdopx;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/5
 * Time: 1:37
 */
class SqlUnionItem
{
    public $all = false;
    /**
     * @var SqlSelector
     */
    public $selector = null;

    public function __construct(SqlSelector $selector, bool $all = false)
    {
        $this->all = $all;
        $this->selector = $selector;
    }
}

class SqlSelector
{

    private $table = null;
    private $alias = null;

    private $sqlTemplate = null;
    private $param = null;

    private $count = -1;
    /**
     * @var SqlItem;
     */
    private $orderItem = null;
    /**
     * @var SqlItem;
     */
    private $groupItem = null;
    /**
     * @var SqlItem;
     */
    private $fieldItem = null;

    private $limit = '';
    /**
     * @var SqlItem;
     */
    private $joinItem = null;
    /**
     * @var SqlCondition;
     */
    private $havingItem = null;
    /**
     * @var SqlCondition
     */
    private $condition = null;

    /**
     * @var SqlUnionItem[]
     */
    private $unionItem = null;

    /**
     * 优化查询
     * @var bool
     */
    private $optimize = false;


    /**
     * 获取类实例
     * @param $table
     * @param string $alias
     * @return SqlSelector
     */
    public static function instance(string $table, $alias = '')
    {
        return new SqlSelector($table, $alias);
    }

    /**
     * Sql构造器
     * SqlSelector constructor.
     * @param string $table
     * @param string $alias
     */
    public function __construct(string $table, $alias = '')
    {
        $this->table = $table;
        $this->alias = $alias;
        $this->condition = new SqlCondition();
    }

    /**
     * 使用SQL模板方式
     * @param string $template
     * @param array $param
     */
    public function setTemplate(string $template, array $param)
    {
        $this->sqlTemplate = $template;
        $this->param = $param;
    }

    public function emptyWhere()
    {
        $this->condition->empty();
        return $this;
    }

    /**
     * 设置别名
     * @param $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * 创建一个查询条件
     * @param string $type
     * @return SqlCondition
     */
    public static function newCondition(string $type = 'and')
    {
        return new SqlCondition($type);
    }

    /**
     * 条件查询
     * @param null $sql
     * @param null $args
     * @return $this
     */
    public function where($sql = null, $args = null)
    {
        if ($sql === null) {
            return $this;
        }
        $this->condition->where($sql, $args);
        return $this;
    }

    /**
     * 条件搜索,如果值为空 放弃筛选
     * @param string $sql
     * @param $value
     * @param int $type
     * @param null $format
     * @return $this
     */
    public function search(string $sql, $value, $type = SqlCondition::WITHOUT_EMPTY, $format = null)
    {
        if (substr_count($sql, '??') == 1 && is_array($value)) {
            if (!empty($value)) {
                $temp = [];
                foreach ($value as $item) {
                    $temp[] = '?';
                }
                $sql = str_replace('??', join(',', $temp), $sql);
                $this->condition->where($sql, $value);
            }
            return $this;
        }
        $this->condition->search($sql, $value, $type, $format);
        return $this;
    }

    /**
     * 获取帧
     * @return array
     */
    public function getFrame()
    {
        return $this->condition->getFrame();
    }

    /**
     * 设置查询字段
     * @param string $field
     * @param null $args
     * @return $this
     */
    public function field(string $field, $args = null)
    {
        $this->fieldItem = new SqlItem($field, $args);
        return $this;
    }

    /**
     * 设置排序
     * @param string $order
     * @param null $args
     * @return $this
     */
    public function order(string $order, $args = null)
    {
        $order = trim($order);
        if ($this->orderItem === null) {
            if (!preg_match('@^by\s+@i', $order)) {
                $order = 'by ' . $order;
            }
            $this->orderItem = new SqlItem($order, $args);
        } else {
            if (!preg_match('@^(by|,)\s+@i', $order)) {
                $order = ',' . $order;
            }
            $this->orderItem->add($order, $args);
        }
        return $this;
    }

    public function emptyOrder()
    {
        $this->orderItem = null;
        return $this;
    }

    /**
     * 设置组
     * @param string $group
     * @param null $args
     * @return $this
     */
    public function group(string $group, $args = null)
    {
        $group = trim($group);
        if ($this->groupItem === null) {
            if (!preg_match('@^by\s+@i', $group)) {
                $group = 'by ' . $group;
            }
            $this->groupItem = new SqlItem($group, $args);
        } else {
            if (!preg_match('@^(by|,)\s+@i', $group)) {
                $group = ',' . $group;
            }
            $this->groupItem->add($group, $args);
        }
        return $this;
    }

    public function emptyGroup()
    {
        $this->groupItem = null;
        return $this;
    }

    /**
     * 结果筛选
     * @param null $sql
     * @param null $args
     * @return $this
     */
    public function having($sql = null, $args = null)
    {
        if (!empty($sql)) {
            if ($this->havingItem == null) {
                $this->havingItem = new SqlCondition();
            }
            $this->havingItem->where($sql, $args);
        }
        return $this;
    }

    public function emptyHaving()
    {
        $this->havingItem = null;
        return $this;
    }

    /**
     * join
     * @param string $sql
     * @param null $args
     * @return $this
     */
    public function join(string $sql, $args = null)
    {
        $sql = trim($sql);
        if (!preg_match('@^(left|right|full|outer|inner|join)\s+@', $sql)) {
            $sql = 'join ' . $sql;
        }
        if ($this->joinItem === null) {
            $this->joinItem = new SqlItem($sql, $args);
        } else {
            $this->joinItem->add($sql, $args);
        }
        return $this;
    }

    public function emptyJoin()
    {
        $this->joinItem = null;
        return $this;
    }

    /**
     * leftJoin
     * @param string $sql
     * @param null $args
     * @return $this
     */
    public function leftJoin(string $sql, $args = null)
    {
        $sql = trim($sql);
        $sql = 'left join ' . $sql;
        if ($this->joinItem === null) {
            $this->joinItem = new SqlItem($sql, $args);
        } else {
            $this->joinItem->add($sql, $args);
        }
        return $this;
    }

    public function rightJoin(string $sql, $args = null)
    {
        $sql = trim($sql);
        $sql = 'right join ' . $sql;
        if ($this->joinItem === null) {
            $this->joinItem = new SqlItem($sql, $args);
        } else {
            $this->joinItem->add($sql, $args);
        }
        return $this;
    }

    public function innerJoin(string $sql, $args = null)
    {
        $sql = trim($sql);
        $sql = 'inner join ' . $sql;
        if ($this->joinItem === null) {
            $this->joinItem = new SqlItem($sql, $args);
        } else {
            $this->joinItem->add($sql, $args);
        }
        return $this;
    }

    public function outerJoin(string $sql, $args = null)
    {
        $sql = trim($sql);
        $sql = 'outer join ' . $sql;
        if ($this->joinItem === null) {
            $this->joinItem = new SqlItem($sql, $args);
        } else {
            $this->joinItem->add($sql, $args);
        }
        return $this;
    }

    public function fullJoin(string $sql, $args = null)
    {
        $sql = trim($sql);
        $sql = 'full join ' . $sql;
        if ($this->joinItem === null) {
            $this->joinItem = new SqlItem($sql, $args);
        } else {
            $this->joinItem->add($sql, $args);
        }
        return $this;
    }

    /**
     * Join ON 条件
     * @param string $sql
     * @param null $args
     * @return $this
     */
    public function joinOn(string $sql, $args = null)
    {
        $sql = trim($sql);
        if ($this->joinItem == null) {
            return $this;
        }
        $sql = 'on ' . $sql;
        $this->joinItem->add($sql, $args);
        return $this;
    }

    /**
     * 联合另外一个查询器
     * @param SqlSelector $selector
     * @return $this
     */
    public function union(SqlSelector $selector)
    {
        if ($selector == null) {
            return $this;
        }
        if ($this->unionItem == null) {
            $this->unionItem = [];
        }
        $this->unionItem[] = new SqlUnionItem($selector, false);
        return $this;
    }

    public function emptyUnion()
    {
        $this->unionItem = null;
        return $this;
    }

    /**
     * 联合,运行结果重复
     * @param SqlSelector $selector
     * @return $this
     */
    public function unionAll(SqlSelector $selector)
    {
        if ($selector == null) {
            return $this;
        }
        if ($this->unionItem == null) {
            $this->unionItem = [];
        }
        $this->unionItem[] = new SqlUnionItem($selector, true);
        return $this;
    }

    /**
     * 设置分页
     * @param int $offset
     * @param int $size
     * @return $this
     */
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

    /**
     * 按页码和分页尺寸进行分页
     * @param int $page
     * @param int $size
     * @return $this
     */
    public function pageLimit(int $page = 1, int $size = 20)
    {
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $size;
        if ($offset < 0) {
            $offset = 0;
        }
        $this->limit($offset, $size);
        return $this;
    }

    /**
     * 数据片段
     * @return array
     */
    public function getSegment()
    {
        $data = [];
        $data['table'] = $this->table;
        $field = '*';
        if ($this->fieldItem !== null) {
            $field = $this->fieldItem->sql;
            if ($this->fieldItem->args !== null) {
                $field = Mysql::format($field, $this->fieldItem->args);
            }
        }
        $data['field'] = $field;
        $data['alias'] = $this->alias;
        $data['where'] = '';
        $frame = $this->condition->getFrame();
        if (!empty($frame['sql'])) {
            if (preg_match('@^(AND|OR)\s+@i', $frame['sql'])) {
                $data['where'] = 'where ' . preg_replace('@^(AND|OR)\s+@i', '', $frame['sql']);
            } else {
                $data['where'] = 'where ' . $frame['sql'];
            }
            if ($frame['args'] !== null && is_array($frame['args'])) {
                $data['where'] = Mysql::format($data['where'], $frame['args']);
            }
        }
        $data['order'] = '';
        if ($this->orderItem != null) {
            $orderSql = $this->orderItem->sql;
            $orderArgs = $this->orderItem->args;
            if (!empty($orderSql)) {
                $data['order'] = 'order ' . $orderSql;
            }
            if ($orderArgs !== null && is_array($orderArgs)) {
                $data['order'] = Mysql::format($data['order'], $orderArgs);
            }
        }
        $data['group'] = '';
        if ($this->groupItem != null) {
            $groupSql = $this->groupItem->sql;
            $groupArgs = $this->groupItem->args;
            if (!empty($groupSql)) {
                $data['group'] = 'group ' . $groupSql;
                if ($groupArgs !== null && is_array($groupArgs)) {
                    $data['group'] = Mysql::format($data['group'], $groupArgs);
                }
            }
        }
        $data['having'] = '';
        if ($this->havingItem != null) {
            $frame = $this->havingItem->getFrame();
            if (!empty($frame['sql'])) {
                if (preg_match('@^(AND|OR)\s+@i', $frame['sql'])) {
                    $data['having'] = 'having ' . preg_replace('@^(AND|OR)\s+@i', '', $frame['sql']);
                } else {
                    $data['having'] = 'having ' . $frame['sql'];
                }
                if ($frame['args'] !== null && is_array($frame['args'])) {
                    $data['having'] = Mysql::format($data['having'], $frame['args']);
                }
            }
        }
        $data['union'] = '';
        if ($this->unionItem) {
            $union = [];
            array_unshift($union, '(');
            $union[] = ')';
            foreach ($this->unionItem as $item) {
                $temp = $item->selector->createSql(0);
                $uItem = '';
                if ($item->all) {
                    $uItem = 'union all ( ' . $temp['sql'] . ' )';
                } else {
                    $uItem = 'union ( ' . $temp['sql'] . ' )';
                }
                if (!empty($temp['args'])) {
                    $uItem = Mysql::format($temp, $frame['args']);
                }
                if (!empty($uItem)) {
                    $union[] = $uItem;
                }
            }
            $data['union'] = join(' ', $union);
        }
        $data['join'] = '';
        if ($this->joinItem !== null) {
            $joinSql = $this->joinItem->sql;
            if ($this->joinItem->args !== null) {
                $joinSql = Mysql::format($joinSql, $this->joinItem->args);
            }
            if (!empty($joinSql)) {
                $data['join'] = $joinSql;
            }
        }

        $data['limit'] = $this->limit;
        $data['param'] = $this->param;
        return $data;
    }

    /**
     * 创建sql
     * @param int $type
     * @return array
     */
    public function createSql($type = 0)
    {

        if (!empty($this->sqlTemplate)) {
            $runtimeDir = Config::get('sdopx.runtime_dir', 'runtime');
            $runtimeDir = Utils::trimPath($runtimeDir);
            $runtimeDir = Utils::path(ROOT_DIR, $runtimeDir);
            if ($type == 2) {
                $order = $this->orderItem;
                $limit = $this->limit;
                $this->orderItem = null;
                $this->limit = null;
                $segment = $this->getSegment();
                $this->orderItem = $order;
                $this->limit = $limit;
                $sql = Sdopx::fetchSQL($this->sqlTemplate, $segment, $runtimeDir);
                return ['sql' => 'select count(1) from (' . $sql . ') countTempTable', 'args' => []];
            } else {
                $segment = $this->getSegment();
                $sql = Sdopx::fetchSQL($this->sqlTemplate, $segment, $runtimeDir);
                return ['sql' => $sql, 'args' => []];
            }
        }
        $sqlItems = [];
        $argItems = [];
        //获取查询数量的sql语句
        if ($type == 2) {
            if ($this->groupItem != null || $this->unionItem != null) {
                $order = $this->orderItem;
                $limit = $this->limit;
                $this->orderItem = null;
                $this->limit = null;
                $temp = $this->createSql(0);
                $temp['sql'] = 'select count(1) from (' . $temp['sql'] . ') countTempTable';
                $this->orderItem = $order;
                $this->limit = $limit;
                return $temp;
            } else {
                $sqlItems[] = 'select count(1) from ' . $this->table;
                if (!empty($this->alias)) {
                    $sqlItems[] = ' ' . $this->alias;
                }
            }
        } //优化查询,只适合简单单表sql
        elseif ($type == 1) {
            $alias = 'Zt';
            $findSql = $alias . '.*';
            if ($this->fieldItem !== null) {
                $findSql = $this->fieldItem->sql;
                if ($this->fieldItem->args !== null) {
                    $argItems = array_merge($argItems, $this->fieldItem->args);
                }
            }
            $sqlItems[] = "select {$findSql} from `{$this->table}` {$alias},(select id from `{$this->table}`";
            if (!empty($this->alias)) {
                $sqlItems[] = ' ' . $this->alias;
            }
        } else {
            $findSql = '*';
            if ($this->fieldItem !== null) {
                $findSql = $this->fieldItem->sql;
                if ($this->fieldItem->args !== null) {
                    $argItems = array_merge($argItems, $this->fieldItem->args);
                }
            }
            $sqlItems[] = "select {$findSql} from `{$this->table}`";
            if (!empty($this->alias)) {
                $sqlItems[] = ' ' . $this->alias;
            }
        }
        //JSON
        if ($type == 2 || $type == 0) {
            if ($this->joinItem !== null) {
                $joinSql = $this->joinItem->sql;
                if ($this->joinItem->args !== null) {
                    $argItems = array_merge($argItems, $this->joinItem->args);
                }
                if (!empty($joinSql)) {
                    $sqlItems[] = ' ' . $joinSql;
                }
            }
        }
        //WHERE
        $frame = $this->condition->getFrame();
        if (!empty($frame['sql'])) {
            if (preg_match('@^(AND|OR)\s+@i', $frame['sql'])) {
                $sqlItems[] = 'where ' . preg_replace('@^(AND|OR)\s+@i', '', $frame['sql']);
            } else {
                $sqlItems[] = 'where ' . $frame['sql'];
            }
            if ($frame['args'] !== null && is_array($frame['args'])) {
                $argItems = array_merge($argItems, $frame['args']);
            }
        }
        //GROUP BY
        if ($this->groupItem != null) {
            $groupSql = $this->groupItem->sql;
            $groupArgs = $this->groupItem->args;
            if (!empty($groupSql)) {
                $sqlItems[] = 'group ' . $groupSql;
                if ($groupArgs !== null && is_array($groupArgs)) {
                    $argItems = array_merge($argItems, $groupArgs);
                }
            }
        }
        //处理 havingItem
        if ($this->havingItem != null) {
            $frame = $this->havingItem->getFrame();
            if (!empty($frame['sql'])) {
                if (preg_match('@^(AND|OR)\s+@i', $frame['sql'])) {
                    $sqlItems[] = 'having ' . preg_replace('@^(AND|OR)\s+@i', '', $frame['sql']);
                } else {
                    $sqlItems[] = 'having ' . $frame['sql'];
                }
                if ($frame['args'] !== null && is_array($frame['args'])) {
                    $argItems = array_merge($argItems, $frame['args']);
                }
            }
        }
        //UNION
        if ($type == 2 || $type == 0) {
            if ($this->unionItem) {
                array_unshift($sqlItems, '(');
                $sqlItems[] = ')';
                foreach ($this->unionItem as $item) {
                    $temp = $item->selector->createSql(0);
                    if ($item->all) {
                        $sqlItems[] = 'union all ( ' . $temp['sql'] . ' )';
                    } else {
                        $sqlItems[] = 'union ( ' . $temp['sql'] . ' )';
                    }
                    if (!empty($temp['args'])) {
                        $argItems = array_merge($argItems, $temp['args']);
                    }
                }
            }
        }
        //ORDER BY
        if ($type != 2 && $this->orderItem != null) {
            $orderSql = $this->orderItem->sql;
            $ordeArgs = $this->orderItem->args;
            if (!empty($orderSql)) {
                $sqlItems[] = 'order ' . $orderSql;
            }
            if ($ordeArgs !== null && is_array($ordeArgs)) {
                $argItems = array_merge($argItems, $ordeArgs);
            }
        }
        //LIMIT
        if ($type != 2 && !empty($this->limit)) {
            $sqlItems[] = $this->limit;
        }
        if ($type == 1) {
            $alias = 'Zt';
            $sqlItems[] = ') Y where ' . $alias . '.id=Y.id';
            if ($this->orderItem != null) {
                $orderSql = $this->orderItem->sql;
                $ordeArgs = $this->orderItem->args;
                if (!empty($orderSql)) {
                    $orderSql = preg_replace_callback('@by\s+(`?\w+`?)\s+(desc|asc)@i', function ($math) use ($alias) {
                        return 'by ' . $alias . '.' . $math[1] . ' ' . $math[2];
                    }, $orderSql);
                    $sqlItems[] = 'order ' . $orderSql;
                }
                if ($ordeArgs !== null && is_array($ordeArgs)) {
                    $argItems = array_merge($argItems, $ordeArgs);
                }
            }
        }
        return ['sql' => join(' ', $sqlItems), 'args' => $argItems];
    }

    public function getCount()
    {
        $temp = $this->createSql(2);
        $count = DB::getOne($temp['sql'], $temp['args']);
        if ($count === null) {
            return 0;
        }
        return intval($count);
    }

    public function setCount($count)
    {
        $this->count = $count;
    }

    public function getPageList($size = 20, $pagekey = 'page')
    {
        if ($this->count == -1) {
            $this->count = $this->getCount();
        }
        if (!$this->optimize) {
            $temp = $this->createSql(0);
        } else {
            if ($this->joinItem || $this->groupItem || $this->unionItem) {
                $temp = $this->createSql(0);
            } else {
                $temp = $this->createSql(1);
            }
        }
        $pageList = new PageList($temp['sql'], $temp['args'], $size, $pagekey);
        $pageList->setSelector($this);
        if ($this->count >= 0) {
            $pageList->setCount($this->count);
        }
        return $pageList;
    }

    /**
     * 设置开启或者关闭优化
     * @param bool $value
     */
    public function setOptimize($value)
    {
        $this->optimize = $value;
    }

    /**
     * 获取多条数据
     * @return array
     * @throws \Exception
     */
    public function getList()
    {
        $temp = $this->createSql(0);
        return DB::getList($temp['sql'], $temp['args']);
    }

    public function getListByPageList()
    {
        if (!$this->optimize) {
            $temp = $this->createSql(0);
        } else {
            if ($this->joinItem || $this->groupItem || $this->unionItem) {
                $temp = $this->createSql(0);
            } else {
                $temp = $this->createSql(1);
            }
        }
        return DB::getList($temp['sql'], $temp['args']);
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    public function getRow()
    {
        $temp = $this->createSql(0);
        return DB::getRow($temp['sql'], $temp['args']);
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    public function getOne()
    {
        $temp = $this->createSql(0);
        return DB::getOne($temp['sql'], $temp['args']);
    }

}