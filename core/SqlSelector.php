<?php

namespace beacon;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/5
 * Time: 1:37
 */

class SqlSelector
{


    private $table = '';
    private $alias = '';

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
    private $findItem = null;
    private $limit = '';

    private $joinItem = null;

    private $havingItem = null;
    /**
     * @var SqlCondition
     */
    private $condition = null;


    /**
     * 优化查询
     * @var bool
     */
    private $optimize = true;

    /**
     * 获取类实例
     * @param $table
     * @param string $alias
     * @return SqlSelector
     */
    public static function instance($table, $alias = '')
    {
        return new SqlSelector($table, $alias);
    }

    /**
     * SqlSelector constructor.
     * @param $table
     * @param string $alias
     */
    public function __construct($table, $alias = '')
    {
        $this->table = $table;
        $this->alias = $alias;
        $this->condition = new SqlCondition();
    }


    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    public function createSqlCondition(string $type = 'and')
    {
        return new SqlCondition($type);
    }

    public function where($sql = null, $args = null)
    {
        if ($sql === null) {
            return $this;
        }
        $this->condition->where($sql, $args);
        return $this;
    }

    public function search(string $sql, $value, $type = SqlCondition::WITHOUT_EMPTY, $format = null)
    {
        $this->condition->search($sql, $value, $type, $format);
        return $this;
    }

    public function getFrame()
    {
        return $this->condition->getFrame();
    }

    public function field(string $find, $args = null)
    {
        $this->findItem = new SqlItem($find, $args);
        return $this;
    }

    public function order(string $order, $args = null)
    {
        $order = trim($order);
        if (!preg_match('@^by\s+@i', $order)) {
            $order = 'by ' . $order;
        }
        if ($this->orderItem === null) {
            $this->orderItem = new SqlItem($order, $args);
        } else {
            $this->orderItem->add($order, $args);
        }
        return $this;
    }

    public function group(string $group, $args = null)
    {
        $group = trim($group);
        if (!preg_match('@^by\s+@i', $group)) {
            $group = 'by ' . $group;
        }
        if ($this->groupItem === null) {
            $this->groupItem = new SqlItem($group, $args);
        } else {
            $this->groupItem->add($group, $args);
        }
        return $this;
    }

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

    public function join(string $sql, $args = null)
    {
        $sql = trim($sql);
        if ($this->joinItem === null) {
            $this->joinItem = new SqlItem($sql, $args);
        } else {
            $this->joinItem->add($sql, $args);
        }
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


    public function createSql($type = 0)
    {
        $sqlItems = [];
        $argItems = [];
        if ($type == 2) {
            if ($this->groupItem != null) {
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
        } elseif ($type == 1) {
            $alias = 'Zt';
            $findSql = $alias . '.*';
            if ($this->findItem !== null) {
                $findSql = $this->findItem->sql;
                if ($this->findItem->args !== null) {
                    $argItems = array_merge($argItems, $this->findItem->args);
                }
            }
            $sqlItems[] = "select {$findSql} from `{$this->table}` {$alias},(select id from `{$this->table}`";
            if (!empty($this->alias)) {
                $sqlItems[] = ' ' . $this->alias;
            }
        } else {
            $findSql = '*';
            if ($this->findItem !== null) {
                $findSql = $this->findItem->sql;
                if ($this->findItem->args !== null) {
                    $argItems = array_merge($argItems, $this->findItem->args);
                }
            }
            $sqlItems[] = "select {$findSql} from `{$this->table}`";
            if (!empty($this->alias)) {
                $sqlItems[] = ' ' . $this->alias;
            }
            //加入JOIN
            $joinSql = '';
            if ($this->joinItem !== null) {
                $joinSql = $this->joinItem->sql;
                if ($this->joinItem->args !== null) {
                    $argItems = array_merge($argItems, $this->joinItem->args);
                }
            }
            if (!empty($joinSql)) {
                $sqlItems[] = ' ' . $joinSql;
            }
        }
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
            if ($this->joinItem) {
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
     * 关闭自动优化
     * @param bool $value
     */
    public function closeOptimize()
    {
        $this->optimize = false;
    }

    /**
     * 获取多条数据
     * @param bool $optimize 是否使用优化，默认开启
     * @return array
     * @throws \Exception
     */
    public function getList()
    {
        if (!$this->optimize) {
            $temp = $this->createSql(0);
        } else {
            if (!$this->joinItem && $this->limit) {
                $temp = $this->createSql(1);
            } else {
                $temp = $this->createSql(0);
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

}