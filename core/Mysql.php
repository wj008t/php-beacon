<?php

namespace beacon;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/13
 * Time: 14:03
 */


use \PDO as PDO;

/**
 * sql 语句片段,用于更新插入时使用
 * Class SqlSection
 * @package beacon
 */
class SqlRaw
{
    public $sql = null;
    public $args = null;

    public function __construct(string $sql, $args = null)
    {
        $this->sql = $sql;
        $this->args = $args;
    }

    public function format()
    {
        return Mysql::format($this->sql, $this->args);
    }
}

/**
 * 错误处理
 * Class MysqlException
 * @package beacon
 */
class MysqlException extends \Exception
{

    protected $detail = '';

    public function __construct(string $message = '', $detail = '', int $code = 0, \Throwable $previous = null)
    {
        $this->detail = $detail;
        parent::__construct($message, $code, $previous);
    }

    public function getDetail()
    {
        return $this->detail;
    }
}

/**
 * mysql 数据操作类
 * Class Mysql
 * @package beacon
 */
class Mysql
{
    private static $instance = null;

    /**
     * 获取一个单例
     * @return Mysql|null
     * @throws MysqlException
     */
    public static function instance()
    {
        if (self::$instance == null) {
            $host = Config::get('db.db_host', '127.0.0.1');
            $port = Config::get('db.db_port', 3306);
            $name = Config::get('db.db_name', '');
            $user = Config::get('db.db_user', '');
            $pass = Config::get('db.db_pwd', '');
            $prefix = Config::get('db.db_prefix', 'sl_');
            $charset = Config::get('db.db_charset', 'utf8');
            $timeout = Config::get('db.timeout', 120);

            try {
                self::$instance = new Mysql($host, $port, $name, $user, $pass, $prefix, $charset, $timeout);
            } catch (\PDOException $e) {
                self::$instance = null;
                throw new MysqlException($e->getMessage(), '', $e->getCode(), $e);
            } catch (\Exception $e) {
                self::$instance = null;
                throw new MysqlException($e->getMessage(), '', $e->getCode(), $e);
            }
        }
        return self::$instance;
    }

    /**
     * 编码 sql 语句
     * @param $value
     * @return false|int|string
     */
    public static function escape($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        $type = gettype($value);
        switch ($type) {
            case 'bool':
            case 'boolean':
                return $value ? 1 : 0;
            case 'int':
            case 'integer':
            case 'double':
            case 'float':
                return $value;
            case 'string':
                break;
            case 'array':
            case 'object':
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                break;
            default :
                $value = strval($value);
                break;
        }
        $value = '\'' . preg_replace_callback('@[\0\b\t\n\r\x1a\"\'\\\\]@', function ($m) {
                switch ($m[0]) {
                    case '\0':
                        return '\\0';
                    case '\b':
                        return '\\b';
                    case '\t':
                        return '\\t';
                    case '\n':
                        return '\\n';
                    case '\r':
                        return '\\r';
                    case '\x1a':
                        return '\\Z';
                    case '"':
                        return '\\"';
                    case '\'':
                        return '\\\'';
                    case '\\':
                        return '\\\\';
                    default:
                        return '';
                }
            }, $value) . '\'';
        return $value;
    }

    /**
     * 格式化 sql 语句
     * @param string $sql
     * @param null $args
     * @return null|string|string[]
     */
    public static function format(string $sql, $args = null)
    {
        if ($args == null) {
            return $sql;
        }
        if (!is_array($args)) {
            $args = [$args];
        }
        if (preg_match('@\?@', $sql)) {
            $index = 0;
            $sql = preg_replace_callback('@\?@', function ($match) use (&$args, &$index) {
                if (!isset($args[$index])) {
                    $index++;
                    return '?';
                }
                $value = $args[$index];
                $index++;
                return Mysql::escape($value);
            }, $sql);
        }
        return $sql;
    }


    /**
     * 前缀
     * @var string
     */
    private $prefix = '';
    /**
     * @var \PDO|null
     */
    private $pdo = null;
    /**
     * 事务计数器,防止多次调用
     * @var int
     */
    private $transactionCounter = 0;
    private $_lastSql = '';

    private $link = null;
    private $user = null;
    private $pass = null;
    private $charset = 'utf8';
    private $timeout = 120;

    /**
     * 构造函数
     * Mysql constructor.
     * @param $host
     * @param int $port
     * @param string $name
     * @param string $user
     * @param string $pass
     * @param string $prefix
     * @param string $charset
     * @param int $timeout
     */
    public function __construct($host, $port = 3306, $name = '', $user = '', $pass = '', $prefix = '', $charset = 'utf8', int $timeout = 120)
    {
        $this->prefix = $prefix;
        if (!empty($name)) {
            $link = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name;
        } else {
            $link = 'mysql:host=' . $host . ';port=' . $port . ';';
        }
        $this->link = $link;
        $this->user = $user;
        $this->pass = $pass;
        $this->charset = $charset;
        $this->timeout = $timeout;
        try {
            if ($timeout == 0) {
                $this->pdo = new PDO($link, $user, $pass, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->charset]);
            } else {
                $this->pdo = new PDO($link, $user, $pass, [PDO::ATTR_PERSISTENT => true, PDO::ATTR_TIMEOUT => $timeout, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->charset]);
            }
        } catch (\PDOException $exc) {
            throw $exc;
        }
    }

    public function reconnection()
    {
        try {
            if ($this->timeout == 0) {
                $this->pdo = new PDO($this->link, $this->user, $this->pass, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->charset]);
            } else {
                $this->pdo = new PDO($this->link, $this->user, $this->pass, [PDO::ATTR_PERSISTENT => true, PDO::ATTR_TIMEOUT => $this->timeout, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->charset]);
            }
        } catch (\PDOException $exc) {
            throw $exc;
        }
    }

    /**
     * 开启事务
     * @return bool
     */
    public function beginTransaction()
    {
        if (!$this->transactionCounter++) {
            return $this->pdo->beginTransaction();
        }
        $this->pdo->exec('SAVEPOINT trans' . $this->transactionCounter);
        return $this->transactionCounter >= 0;
    }

    /**
     * 是否在事务里面
     * @return bool
     */
    public function inTransaction()
    {
        return $this->transactionCounter > 0;
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit()
    {
        if (!--$this->transactionCounter) {
            return $this->pdo->commit();
        }
        return $this->transactionCounter >= 0;
    }

    /**
     * 回滚事务
     * @return bool
     * @throws MysqlException
     */
    public function rollBack()
    {
        if (--$this->transactionCounter) {
            $this->exec('ROLLBACK TO trans' . ($this->transactionCounter + 1));
            return true;
        }
        return $this->pdo->rollBack();
    }

    /**
     * 执行sql
     * @param string $sql
     * @return int
     * @throws MysqlException
     */
    public function exec(string $sql)
    {
        try {
            $sql = str_replace('@pf_', $this->prefix, $sql);
            $ret = $this->pdo->exec($sql);
            return $ret;
        } catch (\Exception $exception) {
            throw new MysqlException($exception->getMessage(), $sql, $exception->getCode(), $exception);
        }
    }

    /**
     * 获取最后的插入的id
     * @param null $name
     * @return string
     */
    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * 获取最后执行语句,需要开启 DEBUG_MYSQL_LOG
     * @return string
     */
    public function lastSql()
    {
        return $this->_lastSql;
    }

    /**
     * 执行sql 语句
     * @param string $sql
     * @param null $args
     * @return bool|\PDOStatement
     * @throws MysqlException
     */
    public function execute(string $sql, $args = null)
    {
        $sql = str_replace('@pf_', $this->prefix, $sql);
        if ($args !== null && !is_array($args)) {
            $args = [$args];
        }
        $time = 0;
        if (defined('DEBUG_MYSQL_LOG') && DEBUG_MYSQL_LOG) {
            $time = microtime(true);
        }
        try {
            $retry = 0;
            redo:
            $sth = $this->pdo->prepare($sql);
            $ret = false;
            try {
                $ret = $sth->execute($args);
            } catch (\Exception $exception) {
                $ret = false;
            }
            if ($ret === FALSE) {
                $err = $sth->errorInfo();
                if (isset($err[1]) && $err[1] == 2006) {
                    $this->reconnection();
                    if ($retry == 0) {
                        $retry = 1;
                        goto redo;
                    }
                }
                $this->_lastSql = Mysql::format($sql, $args);
                if (isset($err[2])) {
                    throw new MysqlException('execute sql statement error:' . $err[0] . ',' . $err[1] . ',' . $err[2], $this->_lastSql);
                } else {
                    throw new MysqlException('execute sql statement error:', $this->_lastSql);
                }
            }
            if (defined('DEBUG_MYSQL_LOG') && DEBUG_MYSQL_LOG) {
                $this->_lastSql = Mysql::format($sql, $args);
                Logger::info($this->_lastSql, microtime(true) - $time);
            }
            return $sth;
        } catch (\Exception $exception) {
            $this->_lastSql = Mysql::format($sql, $args);
            if (defined('DEBUG_MYSQL_LOG') && DEBUG_MYSQL_LOG) {
                Logger::info($this->_lastSql, microtime(true) - $time);
            }
            throw new MysqlException($exception->getMessage(), $this->_lastSql, $exception->getCode(), $exception);
        }
    }

    /**
     * 获取多行记录
     * @param string $sql
     * @param null $args
     * @param null $fetch_style
     * @param null $fetch_argument
     * @param array|null $ctor_args
     * @return array
     * @throws MysqlException
     */
    public function getList(string $sql, $args = null, $fetch_style = null, $fetch_argument = null, array $ctor_args = null)
    {
        if ($fetch_style === null) {
            $fetch_style = PDO::FETCH_ASSOC;
        }
        $stm = $this->execute($sql, $args);
        if ($fetch_style !== null && $fetch_argument !== null && $ctor_args !== null) {
            $rows = $stm->fetchAll($fetch_style, $fetch_argument, $ctor_args);
        } elseif ($fetch_style !== null && $fetch_argument !== null) {
            $rows = $stm->fetchAll($fetch_style, $fetch_argument);
        } elseif ($fetch_style !== null) {
            $rows = $stm->fetchAll($fetch_style);
        } else {
            $rows = $stm->fetchAll();
        }
        $stm->closeCursor();
        return $rows;
    }

    /**
     * 获取单行记录
     * @param string $sql
     * @param null $args
     * @param null $fetch_style
     * @param null $cursor_orientation
     * @param int $cursor_offset
     * @return mixed|null
     * @throws MysqlException
     */
    public function getRow(string $sql, $args = null, $fetch_style = null, $cursor_orientation = null, $cursor_offset = 0)
    {
        if ($fetch_style === null) {
            $fetch_style = PDO::FETCH_ASSOC;
        }
        $stm = $this->execute($sql, $args);
        $row = $stm->fetch($fetch_style, $cursor_orientation, $cursor_offset);
        $stm->closeCursor();
        return $row === false ? null : $row;
    }

    /**
     * 获得单个字段内容
     * @param string $sql
     * @param null $args
     * @param null $field
     * @return mixed|null
     * @throws MysqlException
     */
    public function getOne(string $sql, $args = null, $field = null)
    {
        $row = $this->getRow($sql, $args);
        if ($row == null) {
            return null;
        }
        if (is_string($field) && !empty($field)) {
            return isset($row[$field]) ? $row[$field] : null;
        }
        return current($row);
    }

    /**
     * 获取某个字段的最大值
     * @param string $tbname
     * @param string $field
     * @param null $where
     * @param null $args
     * @return null
     * @throws MysqlException
     */
    public function getMax(string $tbname, string $field, $where = null, $args = null)
    {
        $sql = "select max(`{$field}`) from {$tbname}";
        if ($where !== null) {
            $where = trim($where);
            if ($args != null) {
                $args = is_array($args) ? $args : [$args];
            }
            if (is_int($where) || is_numeric($where)) {
                $args = [intval($where)];
                $where = 'id=?';
            }
            $sql .= ' where ' . $where;
        }
        $row = $this->getRow($sql, $args, PDO::FETCH_NUM);
        if ($row == null) {
            return null;
        }
        return $row[0];
    }

    /**
     * 获取某个字段的最小值
     * @param string $tbname
     * @param string $field
     * @param null $where
     * @param null $args
     * @return null
     * @throws MysqlException
     */
    public function getMin(string $tbname, string $field, $where = null, $args = null)
    {
        $sql = "select min(`{$field}`) from {$tbname}";
        if ($where !== null) {
            $where = trim($where);
            if ($args != null) {
                $args = is_array($args) ? $args : [$args];
            }
            if (is_int($where) || is_numeric($where)) {
                $args = [intval($where)];
                $where = 'id=?';
            }
            $sql .= ' where ' . $where;
        }
        $row = $this->getRow($sql, $args, PDO::FETCH_NUM);
        if ($row == null) {
            return null;
        }
        return $row[0];
    }

    /**
     * 创建一个sql语句原义片段,一般用于更新 插入数据时数组的值
     * @param string $sql
     * @param null $args
     * @return SqlRaw
     */
    public function raw(string $sql, $args = null)
    {
        $data = new SqlRaw($sql, $args);
        return $data;
    }

    /**
     * 插入记录
     * @param string $tbname
     * @param array $values
     * @throws MysqlException
     */
    public function insert(string $tbname, array $values = [])
    {
        if (count($values) == 0) {
            return;
        }
        $names = [];
        $vals = [];
        $temp = [];
        foreach ($values as $key => $item) {
            $names[] = '`' . $key . '`';
            if ($item === null) {
                $vals [] = 'NULL';
            } else if ($item instanceof SqlRaw) {
                $vals [] = $item->sql;
                if (is_array($item->args)) {
                    foreach ($item->args as $it) {
                        $temp[] = $it;
                    }
                } elseif ($item->args !== null) {
                    $temp[] = $item->args;
                }
            } else {
                $vals [] = '?';
                $type = gettype($item);
                switch ($type) {
                    case 'bool':
                    case 'boolean':
                        $temp[] = $item ? 1 : 0;
                        break;
                    case 'int':
                    case 'integer':
                    case 'double':
                    case 'float':
                    case 'string':
                        $temp[] = $item;
                        break;
                        break;
                    case 'array':
                    case 'object':
                        $temp[] = json_encode($item, JSON_UNESCAPED_UNICODE);
                        break;
                    default :
                        $temp[] = strval($item);
                        break;
                }
            }
        }
        $sql = 'insert into ' . $tbname . '(' . join(',', $names) . ') values (' . join(',', $vals) . ')';
        $Stm = $this->execute($sql, $temp);
        $Stm->closeCursor();
    }

    /**
     * 替换记录集
     * @param string $tbname
     * @param array $values
     * @throws MysqlException
     */
    public function replace(string $tbname, array $values = [])
    {
        if (count($values) == 0) {
            return;
        }
        $names = [];
        $vals = [];
        $temp = [];
        foreach ($values as $key => $item) {
            $names[] = '`' . $key . '`';
            if ($item === null) {
                $vals [] = 'NULL';
            } else if ($item instanceof SqlRaw) {
                $vals [] = $item->sql;
                if (is_array($item->args)) {
                    foreach ($item->args as $it) {
                        $temp[] = $it;
                    }
                } elseif ($item->args !== null) {
                    $temp[] = $item->args;
                }
            } else {
                $vals [] = '?';
                $type = gettype($item);
                switch ($type) {
                    case 'bool':
                    case 'boolean':
                        $temp[] = $item ? 1 : 0;
                        break;
                    case 'int':
                    case 'integer':
                    case 'double':
                    case 'float':
                    case 'string':
                        $temp[] = $item;
                        break;
                        break;
                    case 'array':
                    case 'object':
                        $temp[] = json_encode($item, JSON_UNESCAPED_UNICODE);
                        break;
                    default :
                        $temp[] = strval($item);
                        break;
                }
            }
        }
        $sql = 'replace into ' . $tbname . '(' . join(',', $names) . ') values (' . join(',', $vals) . ')';
        $Stm = $this->execute($sql, $temp);
        $Stm->closeCursor();
    }

    /**
     * 更新记录集
     * @param string $tbname
     * @param array $values
     * @param null $where
     * @param null $args
     * @throws MysqlException
     */
    public function update(string $tbname, array $values, $where = null, $args = null)
    {
        if (count($values) == 0) {
            return;
        }
        $where = trim($where);
        if (is_int($where) || is_numeric($where)) {
            $args = [intval($where)];
            $where = 'id=?';
        }
        $maps = [];
        $temp = [];
        foreach ($values as $key => $item) {
            if ($item === null) {
                $maps [] = '`' . $key . '`=NULL';
            } else if ($item instanceof SqlRaw) {
                $maps [] = '`' . $key . '`=' . $item->sql;
                if (is_array($item->args)) {
                    foreach ($item->args as $it) {
                        $temp[] = $it;
                    }
                } elseif ($item->args !== null) {
                    $temp[] = $item->args;
                }
            } else {
                $maps [] = '`' . $key . '`=?';
                $type = gettype($item);
                switch ($type) {
                    case 'bool':
                    case 'boolean':
                        $temp[] = $item ? 1 : 0;
                        break;
                    case 'int':
                    case 'integer':
                    case 'double':
                    case 'float':
                    case 'string':
                        $temp[] = $item;
                        break;
                        break;
                    case 'array':
                    case 'object':
                        $temp[] = json_encode($item, JSON_UNESCAPED_UNICODE);
                        break;
                    default :
                        $temp[] = strval($item);
                        break;
                }
            }
        }
        $sql = 'update ' . $tbname . ' set ' . join(',', $maps);
        if (empty($where)) {
            throw new MysqlException('编辑数据必须带有条件');
        }
        $sql .= ' where ' . $where;
        if (is_array($args)) {
            foreach ($args as $it) {
                $temp[] = $it;
            }
        } elseif ($args !== null) {
            $temp[] = $args;
        }
        $Stm = $this->execute($sql, $temp);
        $Stm->closeCursor();
    }

    /**
     * 删除记录集
     * @param string $tbname
     * @param null $where
     * @param null $args
     * @throws MysqlException
     */
    public function delete(string $tbname, $where = null, $args = null)
    {
        $where = trim($where);
        if (is_int($where) || is_numeric($where)) {
            $args = [intval($where)];
            $where = 'id=?';
        }
        $sql = 'DELETE FROM ' . $tbname;
        if (empty($where)) {
            throw new MysqlException('删除数据必须带有条件');
        }
        $sql .= ' where ' . $where;
        $Stm = $this->execute($sql, $args);
        $Stm->closeCursor();
    }

    /**
     * 获取表字段
     * @param string $tbname
     * @return array
     * @throws MysqlException
     */
    public function getFields(string $tbname)
    {
        return $this->getList('show full fields from `' . $tbname . '`');
    }

    /**
     * 判断字段是否存在
     * @param string $tbname
     * @param string $field
     * @return bool
     * @throws MysqlException
     */
    public function existsField(string $tbname, string $field)
    {
        return $this->getRow('DESCRIBE `' . $tbname . '` `' . $field . '`;') !== null;
    }

    /**
     * 创建数据库表
     * @param string $tbname
     * @param array $options
     * @throws MysqlException
     */
    public function createTable(string $tbname, array $options = [])
    {
        $options = array_merge([
            'engine' => 'InnoDB',
            'charset' => Config::get('db.db_charset', 'utf8'),
            'comment' => '',
        ], $options);
        if ($this->existsTable($tbname)) {
            throw new MysqlException("数据库表已经存在,{$tbname}");
        }
        $sql = "create table `{$tbname}` (`id` int(11) not null auto_increment,primary key (`id`)) engine={$options['engine']} default charset={$options['charset']} comment=?";
        $stm = $this->execute($sql, [$options['comment']]);
        if (!$stm) {
            throw new MysqlException("创建数据库表失败,{$tbname}");
        }
    }

    /**
     * 添加字段
     * @param string $tbname
     * @param string $field
     * @param array $options
     * @return int
     * @throws MysqlException
     */
    public function addField(string $tbname, string $field, array $options = [])
    {
        $options = array_merge([
            'type' => 'VARCHAR',
            'len' => 250,
            'scale' => 0,
            'def' => null,
            'comment' => '',
        ], $options);

        list($type, $len, $scale, $def, $comment) = array_values($options);
        $type = strtoupper($type);
        $sql = "ALTER TABLE {$tbname} ADD `{$field}`";
        switch ($type) {
            case 'VARCHAR':
            case 'INT':
            case 'BIGINT':
            case 'SMALLINT':
            case 'INTEGER':
            case 'TINYINT':
                $sql .= $type . '(' . $len . ')';
                break;
            case 'DECIMAL':
            case 'DOUBLE':
            case 'FLOAT':
                $sql .= $type . '(' . $len . ',' . $scale . ')';
                break;
            default:
                $sql .= $type;
                break;
        }
        if (!in_array(strtoupper($type), ['BLOB', 'TEXT', 'GEOMETRY', 'JSON'])) {
            $sql .= ' DEFAULT ' . Mysql::escape($def);
        }
        if ($comment) {
            $sql .= ' COMMENT ' . Mysql::escape($comment);
        }
        $sql .= ';';
        return $this->exec($sql);
    }

    /**
     * 修改字段
     * @param string $tbname
     * @param string $field
     * @param array $options
     * @return int
     * @throws MysqlException
     */
    public function modifyField(string $tbname, string $field, array $options = [])
    {
        $chkNew = $this->existsField($tbname, $field);
        if (!$chkNew) {
            return $this->addField($tbname, $field, $options);
        }
        $options = array_merge([
            'type' => 'VARCHAR',
            'len' => 250,
            'scale' => 0,
            'def' => null,
            'comment' => '',
        ], $options);
        list($type, $len, $scale, $def, $comment) = array_values($options);
        $type = strtoupper($type);
        $sql = "ALTER TABLE {$tbname} MODIFY `{$field}`";
        switch ($type) {
            case 'VARCHAR':
            case 'INT':
            case 'BIGINT':
            case 'SMALLINT':
            case 'INTEGER':
            case 'TINYINT':
                $sql .= $type . '(' . $len . ')';
                break;
            case 'DECIMAL':
            case 'DOUBLE':
            case 'FLOAT':
                $sql .= $type . '(' . $len . ',' . $scale . ')';
                break;
            default:
                $sql .= $type;
                break;
        }
        $sql .= ' DEFAULT ' . Mysql::escape($def);
        if ($comment) {
            $sql .= ' COMMENT ' . Mysql::escape($comment);
        }
        $sql .= ';';
        return $this->exec($sql);
    }

    /**
     * 更新字段
     * @param string $tbname
     * @param string $oldfield
     * @param string $newfield
     * @param array $options
     * @return int
     * @throws MysqlException
     */
    public function updateField(string $tbname, string $oldfield, string $newfield, array $options = [])
    {
        if ($oldfield == $newfield) {
            return $this->modifyField($tbname, $newfield, $options);
        }
        $chkNew = $this->existsField($tbname, $newfield);
        if ($chkNew) {
            return $this->modifyField($tbname, $newfield, $options);
        }
        $chkOld = $this->existsField($tbname, $oldfield);
        if (!$chkOld && !$chkNew) {
            return $this->addField($tbname, $newfield, $options);
        }

        $options = array_merge([
            'type' => 'VARCHAR',
            'len' => 250,
            'scale' => 0,
            'def' => null,
            'comment' => '',
        ], $options);
        list($type, $len, $scale, $def, $comment) = array_values($options);
        $type = strtoupper($type);
        $sql = "ALTER TABLE {$tbname} CHANGE `{$oldfield}` `{$newfield}`";
        switch ($type) {
            case 'VARCHAR':
            case 'INT':
            case 'BIGINT':
            case 'SMALLINT':
            case 'INTEGER':
            case 'TINYINT':
                $sql .= $type . '(' . $len . ')';
                break;
            case 'DECIMAL':
            case 'DOUBLE':
            case 'FLOAT':
                $sql .= $type . '(' . $len . ',' . $scale . ')';
                break;
            default:
                $sql .= $type;
                break;
        }
        $sql .= ' DEFAULT ' . Mysql::escape($def);
        if ($comment) {
            $sql .= ' COMMENT ' . Mysql::escape($comment);
        }
        $sql .= ';';
        return $this->exec($sql);
    }

    /**
     * 删除字段
     * @param string $tbname
     * @param string $field
     * @return int|null
     * @throws MysqlException
     */
    public function dropField(string $tbname, string $field)
    {
        if ($this->existsField($tbname, $field)) {
            $sql = "ALTER TABLE {$tbname} DROP `{$field}`;";
            return $this->exec($sql);
        }
        return null;
    }

    /**
     * 检查表是否存在
     * @param string $tbname
     * @return bool
     * @throws MysqlException
     */
    public function existsTable(string $tbname)
    {
        $tbname = str_replace('@pf_', $this->prefix, $tbname);
        $row = $this->getRow('SHOW TABLES LIKE ?;', $tbname);
        return $row != null;
    }

    /**
     * 删除表
     * @param string $tbname
     * @return int
     * @throws MysqlException
     */
    public function dropTable(string $tbname)
    {
        $tbname = str_replace('@pf_', $this->prefix, $tbname);
        return $this->exec('DROP TABLE IF EXISTS ' . Mysql::escape($tbname) . ';');
    }
}
