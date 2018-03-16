<?php

namespace beacon;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/13
 * Time: 14:03
 */


use PDO as PDO;
use PDOException as PDOException;
use Throwable;

class SqlSection
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

class MysqlException extends \Exception
{

    protected $stack = '';

    public function __construct(string $message = "", $stack = "", int $code = 0, Throwable $previous = null)
    {
        $this->stack = $stack;
        parent::__construct($message, $code, $previous);
    }

    public function setFile(string $file)
    {
        $this->file = $file;
    }

    public function setLine(int $line)
    {
        $this->line = $line;
    }

    public function setStack(string $stack)
    {
        $this->stack = $stack;
    }

    public function getStack()
    {
        return $this->stack;
    }

    public function __toString()
    {
        return $this->stack . "\n" . parent::__toString();
    }

}

class Mysql
{
    private static $instance = null;
    private $prefix = '';
    /**
     * @var \PDO|null
     */
    private $pdo = null;
    private $transactionCounter = 0;

    private $_lastSql = '';

    public function __construct($host, $port = 3306, $name = '', $user = '', $pass = '', $prefix = '')
    {
        $this->prefix = $prefix;
        if (!empty($name)) {
            $link = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name;
        } else {
            $link = 'mysql:host=' . $host . ';port=' . $port . ';';
        }
        try {
            $this->pdo = new PDO($link, $user, $pass, [PDO::ATTR_PERSISTENT => true, PDO::ATTR_TIMEOUT => 120, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);
        } catch (PDOException $exc) {
            throw $exc;
        }
    }

    public static function instance()
    {
        if (self::$instance == null) {
            $host = Config::get('db.db_host', '127.0.0.1');
            $port = Config::get('db.db_port', 3306);
            $name = Config::get('db.db_name', '');
            $user = Config::get('db.db_user', '');
            $pass = Config::get('db.db_pwd', '');
            $prefix = Config::get('db.db_prefix', 'sl_');
            self::$instance = new Mysql($host, $port, $name, $user, $pass, $prefix);
        }
        return self::$instance;
    }

    public function beginTransaction()
    {
        if (!$this->transactionCounter++) {
            return $this->pdo->beginTransaction();
        }
        $this->pdo->exec('SAVEPOINT trans' . $this->transactionCounter);
        return $this->transactionCounter >= 0;
    }

    public function commit()
    {
        if (!--$this->transactionCounter) {
            return $this->pdo->commit();
        }
        return $this->transactionCounter >= 0;
    }

    public function rollBack()
    {
        if (--$this->transactionCounter) {
            $this->exec('ROLLBACK TO trans' . ($this->transactionCounter + 1));
            return true;
        }
        return $this->pdo->rollBack();
    }

    public function exec(string $sql)
    {
        $time = microtime(true);
        try {
            $sql = str_replace('@pf_', $this->prefix, $sql);
            $ret = $this->pdo->exec($sql);
            return $ret;
        } catch (\Exception $exception) {
            throw new MysqlException($exception->getMessage(), $sql, $exception->getCode(), $exception);
        } finally {
            if (defined('DEV_DEBUG') && DEV_DEBUG) {
                Console::addSql($sql, microtime(true) - $time);
            }
        }
    }

    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }

    public function lastSQL()
    {
        return $this->_lastSql;
    }

    public function execute(string $sql, $args = null)
    {
        $sql = str_replace('@pf_', $this->prefix, $sql);
        if ($args !== null && !is_array($args)) {
            $args = [$args];
        }
        $time = microtime(true);
        $this->_lastSql = Mysql::format($sql, $args);
        Console::log($this->_lastSql);
        try {
            $sth = $this->pdo->prepare($sql);
            if ($sth->execute($args) === FALSE) {
                $err = $sth->errorInfo();
                if (isset($err[2])) {
                    throw new MysqlException('执行语句错误 ' . $err[0] . ',' . $err[1] . ',' . $err[2], $this->_lastSql);
                } else {
                    throw new MysqlException('执行语句错误', $this->_lastSql);
                }
            }
            return $sth;
        } catch (\Exception $exception) {
            throw new MysqlException($exception->getMessage(), $this->_lastSql, $exception->getCode(), $exception);
        } finally {
            if (defined('DEV_DEBUG') && DEV_DEBUG) {
                Console::addSql($this->_lastSql, microtime(true) - $time);
            }
        }
    }

    /**
     * @param string $sql
     * @param null $args
     * @param null $fetch_style
     * @param null $fetch_argument
     * @param array|null $ctor_args
     * @return array
     * @throws \Exception
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
     * @param string $sql
     * @param null $args
     * @param null $fetch_style
     * @param null $cursor_orientation
     * @param int $cursor_offset
     * @return mixed|null
     * @throws \Exception
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
     * @throws \Exception
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

    public function sql(string $sql, $args = null)
    {
        return new SqlSection($sql, $args);
    }

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
            } else if ($item instanceof SqlSection) {
                $vals [] = $item->sql;
                if (is_array($item->args)) {
                    foreach ($item->args as $it) {
                        $temp[] = $it;
                    }
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
            } else if ($item instanceof SqlSection) {
                $vals [] = $item->sql;
                if (is_array($item->args)) {
                    foreach ($item->args as $it) {
                        $temp[] = $it;
                    }
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
            } else if ($item instanceof SqlSection) {
                $maps [] = '`' . $key . '`=' . $item->sql;
                if (is_array($item->args)) {
                    foreach ($item->args as $it) {
                        $temp[] = $it;
                    }
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
        if (!empty($where)) {
            $sql .= ' where ' . $where;
        }
        if (is_array($args)) {
            foreach ($args as $it) {
                $temp[] = $it;
            }
        } else {
            $temp[] = $args;
        }
        $Stm = $this->execute($sql, $temp);
        $Stm->closeCursor();
    }

    public function delete(string $tbname, $where = null, $args = null)
    {
        $where = trim($where);
        if (is_int($where) || is_numeric($where)) {
            $args = [intval($where)];
            $where = 'id=?';
        }
        $sql = 'DELETE FROM ' . $tbname;
        if (!empty($where)) {
            $sql .= ' where ' . $where;
        }
        $Stm = $this->execute($sql, $args);
        $Stm->closeCursor();
    }

    public function getFields(string $tbname)
    {
        return $this->getList('show full fields from `' . $tbname . '`');
    }

    public function existsField(string $tbname, string $field)
    {
        return $this->getRow('DESCRIBE `' . $tbname . '` `' . $field . '`;') !== null;
    }

    public function createTable(string $tbname, array $options = [])
    {
        $options = array_merge([
            'engine' => 'InnoDB',
            'charset' => 'utf8',
            'comment' => '',
        ], $options);
        if ($this->existsTable($tbname)) {
            throw new \Exception("数据库表已经存在,{$tbname}");
        }
        $sql = "create table `{$tbname}` (`id` int(11) not null auto_increment,primary key (`id`)) engine={$options['engine']} default charset={$options['charset']} comment=?";
        $stm = $this->execute($sql, [$options['comment']]);
        if (!$stm) {
            throw new \Exception("创建数据库表失败,{$tbname}");
        }
    }

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
        $sql .= ' DEFAULT ' . Mysql::escape($def);
        if ($comment) {
            $sql .= ' COMMENT ' . Mysql::escape($comment);
        }
        $sql .= ';';
        return $this->exec($sql);
    }

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

    public function dropField(string $tbname, string $field)
    {
        if ($this->existsField($tbname, $field)) {
            $sql = "ALTER TABLE {$tbname} DROP `{$field}`;";
            return $this->exec($sql);
        }
        return null;
    }

    public function existsTable(string $tbname)
    {
        $tbname = str_replace('@pf_', $this->prefix, $tbname);
        $row = $this->getRow('SHOW TABLES LIKE ?;', $tbname);
        return $row != null;
    }

    public function dropTable(string $tbname)
    {
        $tbname = str_replace('@pf_', $this->prefix, $tbname);
        return $this->exec('DROP TABLE IF EXISTS ' . Mysql::escape($tbname) . ';');
    }
}
