<?php


namespace beacon\core;

class DBSelector extends SqlCondition
{
    protected int $_page = 0;
    protected string $_pageKey = 'page';
    protected int $_count = -1;
    protected int $_pageSize = 20;
    protected int $_pageCount = 0;

    protected string $table = '';
    protected string $_limit = '';
    protected ?SqlItem $_fields = null;
    protected ?SqlItem $_orders = null;
    protected ?SqlItem $_groups = null;
    protected ?SqlItem $_joins = null;
    protected ?SqlCondition $_having = null;
    /**
     * @var SqlFrame[]
     */
    protected array $_unions = [];

    public function __construct(string $table)
    {
        parent::__construct();
        $this->table = trim($table);
    }

    /**
     * 要查找的字段集合
     * @param string $fields
     * @param array|string|int|float|bool|null $args
     * @return DBSelector
     */
    public function field(string $fields, array|string|int|float|bool|null $args = null): static
    {
        $fields = trim($fields);
        $this->_fields = new SqlItem($fields, $args);
        return $this;
    }

    /**
     * 分段查询
     * @param int $offset
     * @param int $size
     * @return DBSelector
     */
    public function limit(int $offset = 0, int $size = 0): static
    {
        if ($offset == 0 && $size == 0) {
            $this->_limit = '';
            return $this;
        }
        if ($size <= 0) {
            $this->_limit = 'limit ' . $offset;
        } else {
            $this->_limit = 'limit ' . $offset . ',' . $size;
        }
        return $this;
    }

    /**
     * @param string $order
     * @param array|string|int|float|bool|null $args
     * @return $this
     */
    public function order(string $order, array|string|int|float|bool|null $args = null): static
    {
        $order = trim($order);
        if (preg_match('@^(by\s+|,)@i', $order)) {
            $order = preg_replace('@^(by\s+|,)@i', '', $order);
        }
        if ($order == '') {
            return $this;
        }
        if ($this->_orders === null) {
            $this->_orders = new SqlItem('order by ' . $order, $args);
        } else {
            $this->_orders->add(',' . $order, $args);
        }
        return $this;
    }

    /**
     * @param string $group
     * @param array|string|int|float|bool|null $args
     * @return $this
     */
    public function group(string $group, array|string|int|float|bool|null $args = null): static
    {
        $group = trim($group);
        if (preg_match('@^(by\s+|,)@i', $group)) {
            $group = preg_replace('@^(by\s+|,)@i', '', $group);
        }
        if ($group == '') {
            return $this;
        }
        if ($this->_groups === null) {
            $this->_groups = new SqlItem('group by ' . $group, $args);
        } else {
            $this->_groups->add(',' . $group, $args);
        }
        return $this;
    }

    /**
     * @param string $sql
     * @param array|string|int|float|bool|null $args
     * @return $this
     */
    public function having(string $sql, array|string|int|float|bool|null $args = null): static
    {
        $sql = trim($sql);
        if ($sql == '') {
            return $this;
        }
        if ($this->_having === null) {
            $this->_having = new SqlCondition();
        }
        $this->_having->where($sql, $args);
        return $this;
    }

    /**
     * @param string $table
     * @param array|string|int|float|bool|null $args
     * @return DBSelector
     */
    public function leftJoin(string $table, array|string|int|float|bool|null $args = null): static
    {
        $table = trim($table);
        if ($table == '') {
            return $this;
        }
        $sql = 'left join ' . $table;
        if ($this->_joins == null) {
            $this->_joins = new SqlItem($sql, $args);
        } else {
            $this->_joins->add($sql, $args);
        }
        return $this;
    }

    /**
     * @param string $table
     * @param array|string|int|float|bool|null $args
     * @return DBSelector
     */
    public function rightJoin(string $table, array|string|int|float|bool|null $args = null): static
    {
        $table = trim($table);
        if ($table == '') {
            return $this;
        }
        $sql = 'right join ' . $table;
        if ($this->_joins == null) {
            $this->_joins = new SqlItem($sql, $args);
        } else {
            $this->_joins->add($sql, $args);
        }
        return $this;
    }

    /**
     * @param string $table
     * @param array|string|int|float|bool|null $args
     * @return DBSelector
     */
    public function innerJoin(string $table, array|string|int|float|bool|null $args = null): static
    {
        $table = trim($table);
        if ($table == '') {
            return $this;
        }
        $sql = 'inner join ' . $table;
        if ($this->_joins == null) {
            $this->_joins = new SqlItem($sql, $args);
        } else {
            $this->_joins->add($sql, $args);
        }
        return $this;
    }


    /**
     * @param string $table
     * @param array|string|int|float|bool|null $args
     * @return DBSelector
     */
    public function outerJoin(string $table, array|string|int|float|bool|null $args = null): static
    {
        $table = trim($table);
        if ($table == '') {
            return $this;
        }
        $sql = 'outer join ' . $table;
        if ($this->_joins == null) {
            $this->_joins = new SqlItem($sql, $args);
        } else {
            $this->_joins->add($sql, $args);
        }
        return $this;
    }

    /**
     * join on
     * @param string $sql
     * @param array|string|int|float|bool|null $args
     * @return DBSelector
     * @throws DBException
     */
    public function joinOn(string $sql, array|string|int|float|bool|null $args = null): static
    {
        $sql = trim($sql);
        if ($sql == '') {
            return $this;
        }
        $sql = 'on ' . $sql;
        if ($this->_joins == null) {
            throw new DBException('需要先调用leftJoin|rightJoin|innerJoin|outerJoin方法之后');
        } else {
            $this->_joins->add($sql, $args);
        }
        return $this;
    }

    /**
     * @param string $table
     * @param array|string|int|float|bool|null $args
     * @return DBSelector
     */
    public function fullJoin(string $table, array|string|int|float|bool|null $args = null): static
    {
        $table = trim($table);
        if ($table == '') {
            return $this;
        }
        $sql = 'full join ' . $table;
        if ($this->_joins == null) {
            $this->_joins = new SqlItem($sql, $args);
        } else {
            $this->_joins->add($sql, $args);
        }
        return $this;
    }

    /**
     * 联合
     * @param string $sql
     * @param array|string|int|float|bool|null $args
     * @return $this
     */
    public function union(string $sql, array|string|int|float|bool|null $args = null): static
    {
        $sql = trim($sql);
        if ($sql == '') {
            return $this;
        }
        $frame = new SqlFrame($sql, $args, 'normal');
        $this->_unions[] = $frame;
        return $this;
    }

    /**
     * 联合全部
     * @param string $sql
     * @param array|string|int|float|bool|null $args
     * @return $this
     */
    public function unionAll(string $sql, array|string|int|float|bool|null $args = null): static
    {
        $sql = trim($sql);
        if ($sql == '') {
            return $this;
        }
        $frame = new SqlFrame($sql, $args, 'all');
        $this->_unions[] = $frame;
        return $this;
    }


    /**
     * 解构表名
     * @return array
     */
    protected function deconstructTable(): array
    {
        $table = $this->table;
        $alias = '';
        $oneTable = true; //是否单一表
        $temp = preg_split('@(\s+as\s+|\s+)@i', $table);
        //分析是否单一表
        $len = count($temp);
        if ($len > 2) {
            $oneTable = false;
        } else if ($len == 2) {
            if (preg_match('@[()]@', $temp[0]) || preg_match('@[()]@', $temp[1])) {
                $oneTable = false;
            } else {
                $table = $temp[0];
                $alias = $temp[1];
            }
        }
        if ($oneTable) {
            $table = trim($table, '`');
            $table = '`' . $table . '`';
        }
        return [$oneTable, $table, $alias];
    }

    /**
     * 移除别名
     * @param string $sql
     * @param string $alias
     * @return string
     */
    public function removeAlias(string $sql, string $alias): string
    {
        if ($alias == '') {
            return $sql;
        }
        $sql = preg_replace_callback('@(?:^|[^a-z0-9_]+)' . $alias . '\.(`?[a-z0-9_]+`?\s*)@i', function ($m) {
            return ' ' . $m[1];
        }, $sql);
        return $sql;
    }

    /**
     * 建立SQL 语句
     * @param bool $optimize
     * @return SqlItem
     */
    public function buildSql(bool $optimize = false): SqlItem
    {
        $execSql = [];
        $argItems = [];
        [$oneTable, $table, $alias] = $this->deconstructTable();
        //如果开启优化
        if ($optimize) {
            if (!$oneTable || $this->_joins != null || $this->_limit == '' || $this->_groups != null || $this->_having != null || count($this->_unions) > 0) {
                $optimize = false;
            }
        }
        if ($optimize && preg_match('@limit\s+(\d+),@', $this->_limit, $m)) {
            if (intval($m[1]) < 1000) {
                $optimize = false;
            }
        }
        $findSql = '*';
        if ($this->_fields != null && $this->_fields->sql != '') {
            $findSql = $this->_fields->sql;
            $argItems = array_merge($argItems, $this->_fields->args);
        }
        if ($oneTable) {
            //如果需要优化
            if ($optimize) {
                if ($alias == '') {
                    $alias = 'Ao';
                }
                if ($findSql == '*') {
                    $findSql = $alias . '.*';
                } else {
                    $findTemp = preg_split('@(\s*,\s*)@i', $findSql);
                    foreach ($findTemp as &$item) {
                        if (preg_match('@^`?[a-z0-9_]+`?$@i', $item)) {
                            $item = $alias . '.' . $item;
                        }
                    }
                    $findSql = join(',', $findTemp);
                }
            }
            $execSql[] = 'select ' . $findSql . ' from ' . $table;
            if (!empty($alias)) {
                $execSql[] = $alias;
            }
        } else {
            $execSql[] = 'select ' . $findSql . ' from ' . $this->table;
        }
        //JOIN
        if ($this->_joins != null && $this->_joins->sql != '') {
            $execSql[] = $this->_joins->sql;
            $argItems = array_merge($argItems, $this->_joins->args);
        }
        //优化
        if ($optimize) {
            $execSql[] = ' inner join (select id from ' . $table;
        }
        //查询条件
        $frame = $this->getFrame();
        if ($frame->sql != '') {
            $tempSql = preg_replace('@^(or|and)\s+@i', '', $frame->sql);
            if ($oneTable && $optimize) {
                $tempSql = $this->removeAlias($tempSql, $alias);
            }
            $execSql[] = 'where ' . $tempSql;
            $argItems = array_merge($argItems, $frame->args);
        }
        //Group By
        if ($this->_groups != null && $this->_groups->sql != '') {
            $execSql[] = $this->_groups->sql;
            $argItems = array_merge($argItems, $this->_groups->args);
        }
        //havingItem
        if ($this->_having != null) {
            $frame = $this->_having->getFrame();
            if ($frame->sql != '') {
                $tempSql = preg_replace('@^(or|and)\s+@i', '', $frame->sql);
                $execSql[] = 'having ' . $tempSql;
                $argItems = array_merge($argItems, $frame->args);
            }
        }
        //unions
        if (count($this->_unions) > 0) {
            array_unshift($execSql, '(');
            $execSql[] = ')';
            foreach ($this->_unions as $uFrame) {
                if ($uFrame->type == 'all') {
                    $execSql[] = 'union all ( ' . $uFrame->sql . ')';
                } else {
                    $execSql[] = 'union ( ' . $uFrame->sql . ')';
                }
                $argItems = array_merge($argItems, $uFrame->args);
            }
        }
        //Order By
        if ($this->_orders != null && $this->_orders->sql != '') {
            $tempSql = $this->_orders->sql;
            if ($oneTable && $optimize) {
                $tempSql = $this->removeAlias($tempSql, $alias);
            }
            $execSql[] = $tempSql;
            $argItems = array_merge($argItems, $this->_orders->args);
        }
        //limit
        if ($this->_limit != '') {
            $execSql[] = $this->_limit;
        }
        //优化
        if ($optimize) {
            $execSql[] = ') Zo on ' . $alias . '.id=Zo.id';
            if ($this->_orders != null && $this->_orders->sql != '') {
                $execSql[] = $this->_orders->sql;
                $argItems = array_merge($argItems, $this->_orders->args);
            }
        }
        return new SqlItem(join(' ', $execSql), $argItems);
    }

    /**
     * 生成用于查询数量的SQL语句
     * @return SqlItem
     */
    public function buildCount(): SqlItem
    {
        if ($this->_groups != null || count($this->_unions) != null || $this->_having != null) {
            $order = $this->_orders;
            $this->_orders = null;
            $limit = $this->_limit;
            $this->_limit = '';
            $item = $this->buildSql(false);
            $item->sql = 'select count(1) as mCount from (' . $item->sql . ') CTemp';
            $this->_orders = $order;
            $this->_limit = $limit;
            return $item;
        }
        [$oneTable, $table, $alias] = $this->deconstructTable();
        $execSql = [];
        $argItems = [];
        if ($oneTable) {
            $execSql[] = 'select count(1) as mCount from ' . $table;
            if (!empty($alias)) {
                $execSql[] = $alias;
            }
        } else {
            $execSql[] = 'select count(1) as mCount from ' . $this->table;
        }
        //JOIN
        if ($this->_joins != null && $this->_joins->sql != '') {
            $execSql[] = $this->_joins->sql;
            $argItems = array_merge($argItems, $this->_joins->args);
        }
        //查询条件
        $frame = $this->getFrame();
        if ($frame->sql != '') {
            $tempSql = preg_replace('@^(or|and)\s+@i', '', $frame->sql);
            $execSql[] = 'where ' . $tempSql;
            $argItems = array_merge($argItems, $frame->args);
        }
        return new SqlItem(join(' ', $execSql), $argItems);
    }

    /**
     * 设置分页
     * @param int $pageSize
     * @param string $pageKey
     * @return DBSelector
     */
    public function setPage(int $pageSize = 0, string $pageKey = 'page'): static
    {
        $this->_pageSize = $pageSize;
        $this->_pageKey = $pageKey;
        return $this;
    }

    /**
     * 获取分页数据
     * @return array
     * @throws DBException
     */
    public function pageInfo(): array
    {
        $this->_page = Request::param($this->_pageKey . ':i', 1);
        if ($this->_page < 1) {
            $this->_page = 1;
        }
        if ($this->_count == -1) {
            $this->_count = $this->getCount();
        }
        if ($this->_pageSize < 1) {
            $this->_pageSize = 20;
        }
        $this->_pageCount = ceil($this->_count / $this->_pageSize);
        if ($this->_pageCount == 0) {
            $this->_pageCount = 1;
        }
        return [
            'keyName' => $this->_pageKey,
            'page' => $this->_page,
            'pageCount' => $this->_pageCount,
            'recordsCount' => $this->_count,
            'pageSize' => $this->_pageSize,
        ];
    }

    /**
     * 获取分页数据
     * @return array
     * @throws DBException
     */
    public function pageList(): array
    {
        $this->_page = Request::param($this->_pageKey . ':i', 1);
        if ($this->_page < 1) {
            $this->_page = 1;
        }
        if ($this->_pageSize < 1) {
            $this->_pageSize = 20;
        }
        $offset = ($this->_page - 1) * $this->_pageSize;
        $limit = $this->_limit;
        $this->_limit = 'limit ' . $offset . ',' . $this->_pageSize;
        $item = $this->buildSql(true);
        $this->_limit = $limit;
        return DB::getList($item->sql, $item->args);
    }

    /**
     * 排序
     * @param string $sort
     * @param array $limits
     */
    public function sort(string $sort, array $limits = [])
    {
        if (empty($sort)) {
            return;
        }
        if (preg_match('@^((?:\w+\.)?\w+)-(asc|desc)$@', $sort, $match)) {
            if (count($limits) > 0 && !in_array($match[1], $limits)) {
                return;
            }
            $this->order($match[1] . ' ' . $match[2]);
        }
    }

    /**
     * @return array
     * @throws DBException
     */
    public function pageData(): array
    {
        $data = [];
        $data['list'] = $this->pageList();
        $data['pageInfo'] = $this->pageInfo();
        return $data;
    }

    /**
     * 获取表数据量
     * @return int
     * @throws DBException
     */
    public function getCount(): int
    {
        $item = $this->buildCount();
        $row = DB::getRow($item->sql, $item->args);
        if ($row == null) {
            return 0;
        }
        return intval($row['mCount']);
    }

    /**
     * 获取所有数据
     * @return array
     * @throws DBException
     */
    public function getList(): array
    {
        $item = $this->buildSql($this->_limit != '');
        return DB::getList($item->sql, $item->args);
    }

    /**
     * 获取1行数据
     * @return ?array
     * @throws DBException
     */
    public function getRow(): ?array
    {
        $item = $this->buildSql(false);
        return DB::getRow($item->sql, $item->args);
    }

}