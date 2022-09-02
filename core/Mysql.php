<?php

namespace beacon\core;


/**
 * mysql 数据操作类
 * Class Mysql
 * @package beacon
 */
class Mysql
{
    /**
     * 编码 sql 语句
     * @param $value
     * @return string|int|float
     */
    public static function escape($value): string|int|float
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
                return match ($m[0]) {
                    '\0' => '\\0',
                    '\b' => '\\b',
                    '\t' => '\\t',
                    '\n' => '\\n',
                    '\r' => '\\r',
                    '\x1a' => '\\Z',
                    '"' => '\\"',
                    '\'' => '\\\'',
                    '\\' => '\\\\',
                    default => '',
                };
            }, $value) . '\'';
        return $value;
    }

    /**
     * 格式化 sql 语句
     * @param string $sql
     * @param array|null $args
     * @return string
     */
    public static function format(string $sql, mixed $args = null): string
    {
        if ($args === null) {
            return $sql;
        }
        if (!is_array($args)) {
            $args = [$args];
        }
        if (preg_match('@\?@', $sql)) {
            $index = 0;
            $sql = preg_replace_callback('@\?@', function () use (&$args, &$index) {
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


    protected \PDO $pdo;
    protected int $transactionCounter = 0;

    protected string $_lastSql = '';
    protected string $link = '';
    protected string $user = '';
    protected string $pass = '';
    protected string $charset = 'utf8';
    protected string $prefix = '';
    protected int $timeout = 120;

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
    public function __construct(string $host, int $port = 3306, string $name = '', string $user = '', string $pass = '', string $prefix = '', string $charset = 'utf8', int $timeout = 120)
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
        if ($timeout == 0) {
            $this->pdo = new \PDO($link, $user, $pass, [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->charset]);
        } else {
            $this->pdo = new \PDO($link, $user, $pass, [\PDO::ATTR_PERSISTENT => true, \PDO::ATTR_TIMEOUT => $timeout, \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->charset]);
        }
        $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
    }

    /**
     *重连数据库
     */
    public function reConnection()
    {
        if ($this->timeout == 0) {
            $this->pdo = new \PDO($this->link, $this->user, $this->pass, [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->charset]);
        } else {
            $this->pdo = new \PDO($this->link, $this->user, $this->pass, [\PDO::ATTR_PERSISTENT => true, \PDO::ATTR_TIMEOUT => $this->timeout, \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->charset]);
        }
        $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
    }

    /**
     * 开启事务
     * @return bool
     */
    public function beginTransaction(): bool
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
    public function inTransaction(): bool
    {
        return $this->transactionCounter > 0;
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit(): bool
    {
        if (!--$this->transactionCounter) {
            return $this->pdo->commit();
        }
        return $this->transactionCounter >= 0;
    }

    /**
     * 回滚事务
     * @return bool
     * @throws DBException
     */
    public function rollBack(): bool
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
     * @return int|false
     * @throws DBException
     */
    public function exec(string $sql): int|false
    {
        try {
            $sql = str_replace('@pf_', $this->prefix, $sql);
            return $this->pdo->exec($sql);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            if (!is_int($code)) {
                $code = 0;
            }
            $exception = new DBException($exception->getMessage(), $code, $exception);
            $exception->setDetail($sql);
            throw $exception;
        }
    }

    /**
     * 获取最后的插入的id
     * @param string|null $name
     * @return string
     */
    public function lastInsertId(?string $name = null): string
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * 获取最后执行语句,需要开启 DEBUG_MYSQL_LOG
     * @return string
     */
    public function lastSql(): string
    {
        return $this->_lastSql;
    }

    /**
     * 执行sql 语句
     * @param string $sql
     * @param mixed $args
     * @return false|\PDOStatement
     * @throws DBException
     */
    public function execute(string $sql, mixed $args = null): false|\PDOStatement
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
            $sth = $this->pdo->prepare($sql);
            $ret = $sth->execute($args);
            if ($ret === false) {
                $this->_lastSql = Mysql::format($sql, $args);
                $exception = new DBException('execute sql statement error.');
                $exception->setDetail($this->_lastSql);
                throw $exception;
            }
            if (defined('DEBUG_MYSQL_LOG') && DEBUG_MYSQL_LOG) {
                $time = microtime(true) - $time;
                if (!defined('DEBUG_MYSQL_SLOW_LIMIT') || $time * 1000 > DEBUG_MYSQL_SLOW_LIMIT) {
                    $this->_lastSql = Mysql::format($sql, $args);
                    Logger::sql($this->_lastSql, $time);
                }
            }
            return $sth;
        } catch (\Exception $exception) {
            $this->_lastSql = Mysql::format($sql, $args);
            $code = $exception->getCode();
            if (!is_int($code)) {
                $code = 0;
            }
            $exception = new DBException($exception->getMessage(), $code, $exception);
            $exception->setDetail($this->_lastSql);
            throw $exception;
        }
    }

    /**
     * 获取多行记录
     * @param string $sql
     * @param ?array $args
     * @param int $fetch_style
     * @return array
     * @throws DBException
     */
    public function getList(string $sql, mixed $args = null, int $fetch_style = \PDO::FETCH_ASSOC): array
    {
        $stm = $this->execute($sql, $args);
        $rows = $stm->fetchAll($fetch_style);
        $stm->closeCursor();
        return $rows;
    }

    /**
     * 获取单行记录
     * @param string $sql
     * @param array|null $args
     * @param int $fetch_style
     * @return mixed
     * @throws DBException
     */
    public function getRow(string $sql, mixed $args = null, int $fetch_style = \PDO::FETCH_ASSOC): mixed
    {
        $stm = $this->execute($sql, $args);
        $row = $stm->fetch($fetch_style);
        $stm->closeCursor();
        return $row === false ? null : $row;
    }

    /**
     * 更加ID获取1行数据
     * @param string $table
     * @param int $id
     * @return ?array
     * @throws DBException
     */
    public function getItem(string $table, int $id): ?array
    {
        $sql = 'select * from `' . $table . '` where id=?';
        return $this->getRow($sql, $id);
    }

    /**
     * 获得单个字段内容
     * @param string $sql
     * @param ?array $args
     * @param ?string $field
     * @param mixed|null $def
     * @return mixed
     * @throws DBException
     */
    public function getOne(string $sql, mixed $args = null, ?string $field = null, mixed $def = null): mixed
    {
        $row = $this->getRow($sql, $args);
        if ($row === null) {
            return $def;
        }
        if (is_string($field) && !empty($field)) {
            return Request::lookup($row, $field, $def);
        }
        return current($row);
    }

    /**
     * 获取某个字段的最大值
     * @param string $tbname
     * @param string $field
     * @param string|int|null $where
     * @param mixed $args
     * @return mixed
     * @throws DBException
     */
    public function getMax(string $tbname, string $field, string|int|null $where = null, mixed $args = null): mixed
    {
        $sql = "select max(`{$field}`) from {$tbname}";
        if ($where !== null) {
            $where = trim($where);
            if ($args !== null) {
                $args = is_array($args) ? $args : [$args];
            }
            if (is_int($where) || is_numeric($where)) {
                $args = [intval($where)];
                $where = 'id=?';
            }
            $sql .= ' where ' . $where;
        }
        $row = $this->getRow($sql, $args, \PDO::FETCH_NUM);
        if ($row === null) {
            return null;
        }
        return $row[0];
    }

    /**
     * 获取某个字段的最小值
     * @param string $tbname
     * @param string $field
     * @param string|int|null $where
     * @param mixed $args
     * @return mixed
     * @throws DBException
     */
    public function getMin(string $tbname, string $field, string|int|null $where = null, mixed $args = null): mixed
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
        $row = $this->getRow($sql, $args, \PDO::FETCH_NUM);
        if ($row == null) {
            return null;
        }
        return $row[0];
    }

    /**
     * 创建一个sql语句原义片段,一般用于更新 插入数据时数组的值
     * @param string $sql
     * @param mixed $args
     * @return SqlFrame
     */
    public function raw(string $sql, mixed $args = null): SqlFrame
    {
        return new SqlFrame($sql, $args, 'raw');
    }

    /**
     * 插入记录
     * @param string $tbname
     * @param array $values
     * @throws DBException
     */
    public function insert(string $tbname, array $values = [])
    {
        if (count($values) == 0) {
            return;
        }
        $names = [];
        $params = [];
        $temp = [];
        foreach ($values as $key => $item) {
            $names[] = '`' . $key . '`';
            if ($item === null) {
                $params [] = 'NULL';
            } else if ($item instanceof SqlFrame) {
                $params [] = $item->sql;
                foreach ($item->args as $it) {
                    $temp[] = $it;
                }
            } else {
                $params [] = '?';
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
        $sql = 'insert into ' . $tbname . '(' . join(',', $names) . ') values (' . join(',', $params) . ')';
        $Stm = $this->execute($sql, $temp);
        $Stm->closeCursor();
    }

    /**
     * 替换记录集
     * @param string $tbname
     * @param array $values
     * @throws DBException
     */
    public function replace(string $tbname, array $values = [])
    {
        if (count($values) == 0) {
            return;
        }
        $names = [];
        $params = [];
        $temp = [];
        foreach ($values as $key => $item) {
            $names[] = '`' . $key . '`';
            if ($item === null) {
                $params [] = 'NULL';
            } else if ($item instanceof SqlFrame) {
                $params [] = $item->sql;
                foreach ($item->args as $it) {
                    $temp[] = $it;
                }
            } else {
                $params [] = '?';
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
        $sql = 'replace into ' . $tbname . '(' . join(',', $names) . ') values (' . join(',', $params) . ')';
        $Stm = $this->execute($sql, $temp);
        $Stm->closeCursor();
    }

    /**
     * 更新记录集
     * @param string $tbname
     * @param array $values
     * @param string|int|null $where
     * @param mixed $args
     * @throws DBException
     */
    public function update(string $tbname, array $values, string|int|null $where = null, mixed $args = null)
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
            } else if ($item instanceof SqlFrame) {
                $maps [] = '`' . $key . '`=' . $item->sql;
                foreach ($item->args as $it) {
                    $temp[] = $it;
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
            throw new DBException('编辑数据必须带有条件');
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
     * @param string|int|null $where
     * @param mixed $args
     * @throws DBException
     */
    public function delete(string $tbname, string|int|null $where = null, mixed $args = null)
    {
        $where = trim($where);
        if (is_int($where) || is_numeric($where)) {
            $args = [intval($where)];
            $where = 'id=?';
        }
        $sql = 'delete from ' . $tbname;
        if (empty($where)) {
            throw new DBException('删除数据必须带有条件');
        }
        $sql .= ' where ' . $where;
        $Stm = $this->execute($sql, $args);
        $Stm->closeCursor();
    }

    /**
     * 获取表字段
     * @param string $tbname
     * @return array
     * @throws DBException
     */
    public function getFields(string $tbname): array
    {
        return $this->getList('show full fields from `' . $tbname . '`');
    }

    /**
     * 判断字段是否存在
     * @param string $tbname
     * @param string $field
     * @return bool
     * @throws DBException
     */
    public function existsField(string $tbname, string $field): bool
    {
        return $this->getRow('DESCRIBE `' . $tbname . '` `' . $field . '`;') !== null;
    }

    /**
     * 创建数据库表
     * @param string $tbname
     * @param array $options
     * @throws DBException
     */
    public function createTable(string $tbname, array $options = [])
    {
        $options = array_merge([
            'engine' => 'InnoDB',
            'charset' => Config::get('db.db_charset', 'utf8'),
            'comment' => '',
        ], $options);

        if ($this->existsTable($tbname)) {
            throw new DBException("数据库表已经存在,{$tbname}");
        }
        $sql = "create table `{$tbname}` (`id` int(11) not null auto_increment,primary key (`id`)) engine={$options['engine']} default charset={$options['charset']} comment=".Mysql::escape($options['comment']).';';
        $stm = $this->execute($sql, [$options['comment']]);
        if (!$stm) {
            throw new DBException("创建数据库表失败,{$tbname}");
        }
    }

    /**
     * 添加字段
     * @param string $tbname
     * @param string $field
     * @param array $options
     * @return false|int
     * @throws DBException
     */
    public function addField(string $tbname, string $field, array $options = []): false|int
    {
        $options = array_merge([
            'type' => 'VARCHAR',
            'len' => 250,
            'scale' => 0,
            'def' => null,
            'comment' => '',
        ], $options);

        [$type, $len, $scale, $def, $comment] = array_values($options);
        $type = strtoupper($type);
        $sql = "ALTER TABLE `{$tbname}` ADD `{$field}`";
        $sql .= match ($type) {
            'VARCHAR', 'INT', 'BIGINT', 'SMALLINT', 'INTEGER', 'TINYINT' => $type . '(' . $len . ')',
            'DECIMAL', 'DOUBLE', 'FLOAT' => $type . '(' . $len . ',' . $scale . ')',
            default => $type,
        };
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
     * @return false|int
     * @throws DBException
     */
    public function modifyField(string $tbname, string $field, array $options = []): false|int
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
        [$type, $len, $scale, $def, $comment] = array_values($options);
        $type = strtoupper($type);
        $sql = "ALTER TABLE `{$tbname}` MODIFY `{$field}`";
        $sql .= match ($type) {
            'VARCHAR', 'INT', 'BIGINT', 'SMALLINT', 'INTEGER', 'TINYINT' => $type . '(' . $len . ')',
            'DECIMAL', 'DOUBLE', 'FLOAT' => $type . '(' . $len . ',' . $scale . ')',
            default => $type,
        };
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
     * 更新字段
     * @param string $tbname
     * @param string $oldField
     * @param string $newField
     * @param array $options
     * @return false|int
     * @throws DBException
     */
    public function updateField(string $tbname, string $oldField, string $newField, array $options = []): false|int
    {
        if ($oldField == $newField) {
            return $this->modifyField($tbname, $newField, $options);
        }
        $chkNew = $this->existsField($tbname, $newField);
        if ($chkNew) {
            return $this->modifyField($tbname, $newField, $options);
        }
        $chkOld = $this->existsField($tbname, $oldField);
        if (!$chkOld) {
            return $this->addField($tbname, $newField, $options);
        }
        $options = array_merge([
            'type' => 'VARCHAR',
            'len' => 250,
            'scale' => 0,
            'def' => null,
            'comment' => '',
        ], $options);
        [$type, $len, $scale, $def, $comment] = array_values($options);
        $type = strtoupper($type);
        $sql = "ALTER TABLE `{$tbname}` CHANGE `{$oldField}` `{$newField}`";
        $sql .= match ($type) {
            'VARCHAR', 'INT', 'BIGINT', 'SMALLINT', 'INTEGER', 'TINYINT' => $type . '(' . $len . ')',
            'DECIMAL', 'DOUBLE', 'FLOAT' => $type . '(' . $len . ',' . $scale . ')',
            default => $type,
        };
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
     * 删除字段
     * @param string $tbname
     * @param string $field
     * @return false|int
     * @throws DBException
     */
    public function dropField(string $tbname, string $field): false|int
    {
        if ($this->existsField($tbname, $field)) {
            $sql = "ALTER TABLE `{$tbname}` DROP `{$field}`;";
            return $this->exec($sql);
        }
        return 0;
    }

    /**
     * 检查表是否存在
     * @param string $tbname
     * @return bool
     * @throws DBException
     */
    public function existsTable(string $tbname): bool
    {
        $tbname = str_replace('@pf_', $this->prefix, $tbname);
        $row = $this->getRow('SHOW TABLES LIKE ' . Mysql::escape($tbname) . ';');
        return $row != null;
    }


    /**
     * 删除表
     * @param string $tbname
     * @return int|false
     * @throws DBException
     */
    public function dropTable(string $tbname): int|false
    {
        $tbname = str_replace('@pf_', $this->prefix, $tbname);
        return $this->exec('DROP TABLE IF EXISTS ' . Mysql::escape($tbname) . ';');
    }
}
