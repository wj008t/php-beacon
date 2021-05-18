<?php


namespace beacon\core;


class DB
{
    protected static ?Mysql $engine = null;

    /**
     * 获取Mysql链接实例
     * @return Mysql
     * @throws DBException
     */
    public static function engine(): Mysql
    {
        if (static::$engine != null) {
            return static::$engine;
        }
        $host = Config::get('db.db_host', '127.0.0.1');
        $port = Config::get('db.db_port', 3306);
        $name = Config::get('db.db_name', '');
        $user = Config::get('db.db_user', '');
        $pass = Config::get('db.db_pwd', '');
        $prefix = Config::get('db.db_prefix', 'sl_');
        $charset = Config::get('db.db_charset', 'utf8');
        $timeout = Config::get('db.timeout', 120);
        try {
            static::$engine = new Mysql($host, $port, $name, $user, $pass, $prefix, $charset, $timeout);
        } catch (\PDOException | \Exception $e) {
            static::$engine = null;
            $code = $e->getCode();
            if (!is_int($code)) {
                $code = 0;
            }
            throw new DBException($e->getMessage(), $code, $e);
        }
        return static::$engine;
    }


    /**
     * 开启事务
     * @return bool
     * @throws DBException
     */
    public static function beginTransaction(): bool
    {
        return static::engine()->beginTransaction();
    }

    /**
     * 是否在事务中
     * @return bool
     * @throws DBException
     */
    public static function inTransaction(): bool
    {
        return static::engine()->inTransaction();
    }

    /**
     * 事务闭包
     * @param callable $func
     * @throws \Exception
     */
    public static function transaction(callable $func)
    {
        try {
            static::beginTransaction();
            $func();
            static::commit();
        } catch (\Exception $exception) {
            static::rollBack();
            throw $exception;
        }
    }


    /**
     * 提交事务
     * @return bool
     * @throws DBException
     */
    public static function commit(): bool
    {
        return static::engine()->commit();
    }


    /**
     * 回滚事务
     * @return bool
     * @throws DBException
     */
    public static function rollBack(): bool
    {
        return static::engine()->rollBack();
    }

    /**
     *  执行sql 语句
     * @param string $sql
     * @return false|int
     * @throws DBException
     */
    public static function exec(string $sql): false|int
    {
        return static::engine()->exec($sql);
    }

    /**
     * 获取最后一条sql 语句,需要开启 DEBUG_MYSQL_LOG
     * @return string
     * @throws DBException
     */
    public static function lastSql(): string
    {
        return static::engine()->lastSql();
    }

    /**
     * 获取最后的插入的id
     * @param ?string $name
     * @return string
     * @throws DBException
     */
    public static function lastInsertId(?string $name = null): string
    {
        return static::engine()->lastInsertId($name);
    }

    /**
     * 执行sql 语句
     * @param string $sql
     * @param null $args
     * @return false|\PDOStatement
     * @throws DBException
     */
    public static function execute(string $sql, $args = null): false|\PDOStatement
    {
        return static::engine()->execute($sql, $args);
    }

    /**
     * 获取多行记录
     * @param string $sql
     * @param mixed $args
     * @param int $fetch_style
     * @return array
     * @throws DBException
     */
    public static function getList(string $sql, mixed $args = null, int $fetch_style = \PDO::FETCH_ASSOC): array
    {
        return static::engine()->getList($sql, $args, $fetch_style);
    }

    /**
     * 获取单条数据
     * @param string $sql
     * @param null $args
     * @param int $fetch_style
     * @return mixed
     * @throws DBException
     */
    public static function getRow(string $sql, mixed $args = null, int $fetch_style = \PDO::FETCH_ASSOC): mixed
    {
        return static::engine()->getRow($sql, $args, $fetch_style);
    }

    /**
     * 按ID获取一行记录
     * @param string $table
     * @param int $id
     * @return ?array
     * @throws DBException
     */
    public static function getItem(string $table, int $id): ?array
    {
        return static::engine()->getItem($table, $id);
    }

    /**
     * 获取单个字段值
     * @param string $sql
     * @param mixed $args
     * @param ?string $field
     * @param mixed $def
     * @return mixed
     * @throws DBException
     */
    public static function getOne(string $sql, mixed $args = null, ?string $field = null, mixed $def = null): mixed
    {
        return static::engine()->getOne($sql, $args, $field, $def);
    }

    /**
     * 获得最大值
     * @param string $tbname
     * @param string $field
     * @param string|int|null $where
     * @param mixed $args
     * @return mixed
     * @throws DBException
     */
    public static function getMax(string $tbname, string $field, string|int|null $where = null, mixed $args = null): mixed
    {
        return static::engine()->getMax($tbname, $field, $where, $args);
    }

    /**
     * 获得最小值
     * @param string $tbname
     * @param string $field
     * @param string|int|null $where
     * @param mixed|null $args
     * @return mixed
     * @throws DBException
     */
    public static function getMin(string $tbname, string $field, string|int|null $where = null, mixed $args = null): mixed
    {
        return static::engine()->getMin($tbname, $field, $where, $args);
    }

    /**
     *  创建一个sql语句片段,一般用于更新 插入数据时数组的值
     * @param string $sql
     * @param null $args
     * @return SqlFrame
     * @throws DBException
     */
    public static function raw(string $sql, mixed $args = null): SqlFrame
    {
        return static::engine()->raw($sql, $args);
    }

    /**
     * 插入记录
     * @param string $tbname
     * @param array $values
     * @throws DBException
     */
    public static function insert(string $tbname, array $values = [])
    {
        static::engine()->insert($tbname, $values);
    }

    /**
     * 替换记录集
     * @param string $tbname
     * @param array $values
     * @throws DBException
     */
    public static function replace(string $tbname, array $values = [])
    {
        static::engine()->replace($tbname, $values);
    }

    /**
     * 更新记录集
     * @param string $tbname
     * @param array $values
     * @param string|int|null $where
     * @param mixed $args
     * @throws DBException
     */
    public static function update(string $tbname, array $values, string|int|null $where = null, mixed $args = null)
    {
        static::engine()->update($tbname, $values, $where, $args);
    }

    /**
     * 删除记录集
     * @param string $tbname
     * @param string|int|null $where
     * @param mixed $args
     * @throws DBException
     */
    public static function delete(string $tbname, string|int|null $where = null, mixed $args = null)
    {
        static::engine()->delete($tbname, $where, $args);
    }

    /**
     * 获得表的所有字段
     * @param string $tbname
     * @return array
     * @throws DBException
     */
    public static function getFields(string $tbname): array
    {
        return static::engine()->getFields($tbname);
    }

    /**
     * 对数据表进行过滤字段
     * @param array $data
     * @param string $tbname
     * @return array
     * @throws DBException
     */
    public static function fieldFilter(array $data, string $tbname): array
    {
        $fields = static::engine()->getFields($tbname);
        $temp = [];
        foreach ($fields as $field) {
            $key = $field['Field'];
            if (isset($data[$key])) {
                $temp[$key] = $data[$key];
            }
        }
        return $temp;
    }


    /**
     * 判断字段是否存在
     * @param string $tbname
     * @param string $field
     * @return bool
     * @throws DBException
     */
    public static function existsField(string $tbname, string $field): bool
    {
        return static::engine()->existsField($tbname, $field);
    }

    /**
     * 创建数据库表
     * @param string $tbname
     * @param array $options
     * @throws DBException
     */
    public static function createTable(string $tbname, array $options = [])
    {
        static::engine()->createTable($tbname, $options);
    }

    /**
     * 添加字段
     * @param string $tbname
     * @param string $field
     * @param array $options
     * @return false|int
     * @throws DBException
     */
    public static function addField(string $tbname, string $field, array $options = []): false|int
    {
        return static::engine()->addField($tbname, $field, $options);
    }

    /**
     * 修改字段
     * @param string $tbname
     * @param string $field
     * @param array $options
     * @return false|int
     * @throws DBException
     */
    public static function modifyField(string $tbname, string $field, array $options = []): false|int
    {
        return static::engine()->modifyField($tbname, $field, $options);
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
    public static function updateField(string $tbname, string $oldField, string $newField, array $options = []): false|int
    {
        return static::engine()->updateField($tbname, $oldField, $newField, $options);
    }

    /**
     * 删除字段
     * @param string $tbname
     * @param string $field
     * @return false|int
     * @throws DBException
     */
    public static function dropField(string $tbname, string $field): false|int
    {
        return static::engine()->dropField($tbname, $field);
    }

    /**
     * 检查表存在
     * @param string $tbname
     * @return bool
     * @throws DBException
     */
    public static function existsTable(string $tbname): bool
    {
        return static::engine()->existsTable($tbname);
    }

    /**
     * 删除表
     * @param string $tbname
     * @return false|int
     * @throws DBException
     */
    public static function dropTable(string $tbname): false|int
    {
        return static::engine()->dropTable($tbname);
    }

}