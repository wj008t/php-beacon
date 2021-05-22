<?php

namespace beacon;

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/13
 * Time: 16:51
 */
class DB
{
    protected static $engine = null;

    /**
     * 获取数据库引擎实例
     * @return Mysql|null
     * @throws DBException
     */
    public static function engine()
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
        } catch (\PDOException $e) {
            static::$engine = null;
            throw new DBException($e->getMessage(), '', $e->getCode(), $e);
        } catch (\Exception $e) {
            static::$engine = null;
            throw new DBException($e->getMessage(), '', $e->getCode(), $e);
        }
        return static::$engine;
    }

    /**
     * 开启事务
     * @return bool
     * @throws DBException
     */
    public static function beginTransaction()
    {
        return static::engine()->beginTransaction();
    }

    /**
     * 是否在事物里面
     * @return bool
     * @throws DBException
     */
    public static function inTransaction()
    {
        return static::engine()->inTransaction();
    }

    /**
     * 事务闭包
     * @param $func
     * @throws \Exception
     */
    public static function transaction($func)
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
    public static function commit()
    {
        return static::engine()->commit();
    }

    /**
     * 回滚事务
     * @return bool
     * @throws DBException
     */
    public static function rollBack()
    {
        return static::engine()->rollBack();
    }

    /**
     * 执行sql 语句
     * @param string $sql
     * @return int
     * @throws DBException
     */
    public static function exec(string $sql)
    {
        return static::engine()->exec($sql);
    }

    /**
     * 获取最后一条sql 语句,需要开启 DEBUG_MYSQL_LOG
     * @return string
     * @throws DBException
     */
    public static function lastSql()
    {
        return static::engine()->lastSql();
    }

    /**
     * 获取最后的插入的id
     * @param null $name
     * @return string
     * @throws DBException
     */
    public static function lastInsertId($name = null)
    {
        return static::engine()->lastInsertId($name);
    }

    /**
     * 执行sql 语句
     * @param string $sql
     * @param null $args
     * @return bool|\PDOStatement
     * @throws DBException
     */
    public static function execute(string $sql, $args = null)
    {
        return static::engine()->execute($sql, $args);
    }

    /**
     * 获取多行记录
     * @param string $sql
     * @param null $args
     * @param null $fetch_style
     * @param null $fetch_argument
     * @param array|null $ctor_args
     * @return array
     * @throws DBException
     */
    public static function getList(string $sql, $args = null, $fetch_style = null, $fetch_argument = null, array $ctor_args = null)
    {
        return static::engine()->getList($sql, $args, $fetch_style, $fetch_argument, $ctor_args);
    }

    /**
     * 获取单条数据
     * @param string $sql
     * @param null $args
     * @param null $fetch_style
     * @param null $cursor_orientation
     * @param int $cursor_offset
     * @return mixed|null
     * @throws DBException
     */
    public static function getRow(string $sql, $args = null, $fetch_style = null, $cursor_orientation = null, $cursor_offset = 0)
    {
        return static::engine()->getRow($sql, $args, $fetch_style, $cursor_orientation, $cursor_offset);
    }

    /**
     * 获取单个字段值
     * @param string $sql
     * @param null $args
     * @param null $field
     * @return mixed|null
     * @throws DBException
     */
    public static function getOne(string $sql, $args = null, $field = null)
    {
        return static::engine()->getOne($sql, $args, $field);
    }

    /**
     * 获取某个字段的最大值
     * @param string $tbname
     * @param string $field
     * @param null $where
     * @param null $args
     * @return null
     * @throws DBException
     */
    public static function getMax(string $tbname, string $field, $where = null, $args = null)
    {
        return static::engine()->getMax($tbname, $field, $where, $args);
    }

    /**
     * 获取某个字段的最小值
     * @param string $tbname
     * @param string $field
     * @param null $where
     * @param null $args
     * @return null
     * @throws DBException
     */
    public static function getMin(string $tbname, string $field, $where = null, $args = null)
    {
        return static::engine()->getMin($tbname, $field, $where, $args);
    }

    /**
     *  创建一个sql语句片段,一般用于更新 插入数据时数组的值
     * @param string $sql
     * @param null $args
     * @return SqlRaw
     * @throws DBException
     */
    public static function raw(string $sql, $args = null)
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
        return static::engine()->insert($tbname, $values);
    }

    /**
     * 替换记录集
     * @param string $tbname
     * @param array $values
     * @throws DBException
     */
    public static function replace(string $tbname, array $values = [])
    {
        return static::engine()->replace($tbname, $values);
    }

    /**
     * 更新记录集
     * @param string $tbname
     * @param array $values
     * @param null $where
     * @param null $args
     * @throws DBException
     */
    public static function update(string $tbname, array $values, $where = null, $args = null)
    {
        return static::engine()->update($tbname, $values, $where, $args);
    }

    /**
     * 删除记录集
     * @param string $tbname
     * @param null $where
     * @param null $args
     * @throws DBException
     */
    public static function delete(string $tbname, $where = null, $args = null)
    {
        return static::engine()->delete($tbname, $where, $args);
    }

    /**
     * 获取表字段 判断字段是否存在
     * @param string $tbname
     * @return array
     * @throws DBException
     */
    public static function getFields(string $tbname)
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
    public static function fieldFilter(array $data, string $tbname)
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
    public static function existsField(string $tbname, string $field)
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
        return static::engine()->createTable($tbname, $options);
    }

    /**
     * 添加字段
     * @param string $tbname
     * @param string $field
     * @param array $options
     * @return int
     * @throws DBException
     */
    public static function addField(string $tbname, string $field, array $options = [])
    {
        return static::engine()->addField($tbname, $field, $options);
    }

    /**
     * 修改字段
     * @param string $tbname
     * @param string $field
     * @param array $options
     * @return int
     * @throws DBException
     */
    public static function modifyField(string $tbname, string $field, array $options = [])
    {
        return static::engine()->modifyField($tbname, $field, $options);
    }

    /**
     * 更新字段
     * @param string $tbname
     * @param string $oldfield
     * @param string $newfield
     * @param array $options
     * @return int
     * @throws DBException
     */
    public static function updateField(string $tbname, string $oldfield, string $newfield, array $options = [])
    {
        return static::engine()->updateField($tbname, $oldfield, $newfield, $options);
    }

    /**
     * 删除字段
     * @param string $tbname
     * @param string $field
     * @return int|null
     * @throws DBException
     */
    public static function dropField(string $tbname, string $field)
    {
        return static::engine()->dropField($tbname, $field);
    }

    /**
     * 检查表是否存在
     * @param string $tbname
     * @return bool
     * @throws DBException
     */
    public static function existsTable(string $tbname)
    {
        return static::engine()->existsTable($tbname);
    }

    /**
     * 删除表
     * @param string $tbname
     * @return int
     * @throws DBException
     */
    public static function dropTable(string $tbname)
    {
        return static::engine()->dropTable($tbname);
    }
}
