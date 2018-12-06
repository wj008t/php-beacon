<?php

namespace beacon;

use \PDO as PDO;

/**
 * 数据分页类 of Pagelist
 *
 * @author WJ008
 */
class PageList
{

    private $sql;
    private $page; //当前页面
    private $recordsCount = -1;  //记录数
    private $onlyCount = -1; //仅显示
    private $pageSize; //页面大小
    private $pageCount; //最大页数
    private $key;
    private $args;
    private $info = null;
    /**
     * @var SqlSelector
     */
    private $selector = null;

    /**
     * 数据库分页类 <br/>
     * (MysqlDB &gt;= 1.0.0, SaMao &gt;= 1.0.0)<br/>
     * @param string $sql 要执行的SQL语句
     * @param array $args sql参数数组
     * @param int $size 分页大小
     * @param string $key 分页的URL名称$_GET['?']
     */
    public function __construct($sql, $args = array(), $size = 20, $key = 'page')
    {
        $this->sql = $sql;
        $this->pageSize = intval($size);
        $this->key = $key;
        $this->args = $args;
        $this->page = intval(isset($_REQUEST[$key]) ? $_REQUEST[$key] : '1');
        $this->pageCount = -1;
    }

    /**
     * 直接给定记录数可以提高查询效率
     * @param int $count 记录数
     */
    public function setCount($count)
    {
        $this->recordsCount = $count;
    }

    public function setSelector(SqlSelector $selector)
    {
        $this->selector = $selector;
    }

    /**
     * 设置最大分页行数
     * @param int $count 只显示几条记录
     */
    public function setOnlyCount($count)
    {
        $this->onlyCount = $count;
    }

    private function getPageCount($count, $size)
    {
        if (($count % $size) == 0) {
            $pageCount = ($count / $size);
        } else {
            $pageCount = (int)($count / $size) + 1;
        }
        if ($pageCount == 0) {
            $pageCount = 1;
        }
        return $pageCount;
    }

    /**
     * 获取 分页信息数据
     * array(<br/>
     * 'page_key'      ：分页参数名称 如果 page,<br/>
     * 'query'         ：本页所附带的连接 即 $_SERVER['REQUEST_URI'] <br/>例如 cid=3&keyword=abc&page=3<br/>
     * 'other_query'   ：除了page 外的所有 附带参数, <br/>例如 cid=3&keyword=abc 不带页码参数<br/>
     * 'page'          ：当前 页码,<br/>
     * 'page_count'    ：页数，即最大页数,<br/>
     * 'records_count' ：记录数,<br/>
     * 'size'          ：分页尺度，即按多少条记录为一页,<br/>
     * )
     * @return array|null
     * @throws \Exception
     */
    public function getInfo()
    {
        if ($this->info != null) {
            return $this->info;
        }
        if ($this->recordsCount == -1) {
            if ($this->selector) {
                $this->recordsCount = $this->selector->getCount();
            } else {
                if (strripos($this->sql, ' from ') === stripos($this->sql, ' from ')) {
                    $sql = preg_replace('@^select\s+(distinct\s+[a-z][a-z0-9]+\s*,)?(.*)\s+from\s+@', 'select $1count(1) from ', $this->sql);
                    $row = DB::getRow($sql, $this->args, PDO::FETCH_NUM);
                } else {
                    $row = DB::getRow('select count(1) from (' . $this->sql . ') MyTempTable', $this->args, PDO::FETCH_NUM);
                }
                $this->recordsCount = $row[0];
            }
        }
        if ($this->onlyCount == -1 || $this->onlyCount > $this->recordsCount) {
            $this->onlyCount = $this->recordsCount;
        }
        if ($this->pageCount == -1) {
            $this->pageCount = $this->getPageCount($this->onlyCount, $this->pageSize);
        }
        if ($this->page <= 0) {
            $this->page = 1;
        }
        if ($this->page > $this->pageCount) {
            $this->page = $this->pageCount;
        }
        $this->info = array(
            'keyName' => $this->key,
            'page' => $this->page,
            'pageCount' => $this->pageCount,
            'recordsCount' => $this->recordsCount,
            'onlyCount' => $this->onlyCount,
            'pageSize' => $this->pageSize,
        );
        return $this->info;
    }

    /**
     *  获取 分页后的记录集数据
     * @param null $fetch_style
     * @param null $fetch_argument
     * @param array|null $ctor_args
     * @return array
     * @throws \Exception
     */
    public function getList($fetch_style = null, $fetch_argument = null, array $ctor_args = null)
    {
        $this->getInfo();
        $start = ($this->page - 1) * $this->pageSize;
        if ($start < 0) {
            $start = 0;
        }
        if ($this->selector) {
            $this->selector->limit($start, $this->pageSize);
            return $this->selector->getListByPageList();
        }
        $sql = $this->sql . ' limit ' . $start . ' , ' . $this->pageSize;
        return DB::getList($sql, $this->args, $fetch_style, $fetch_argument, $ctor_args);
    }

    /**
     * 获取所有数据
     * @param null $fetch_style
     * @param null $fetch_argument
     * @param array|null $ctor_args
     * @return array
     * @throws \Exception
     */
    public function getAll($fetch_style = null, $fetch_argument = null, array $ctor_args = null)
    {
        return DB::getList($this->sql, $this->args, $fetch_style, $fetch_argument, $ctor_args);
    }
}
