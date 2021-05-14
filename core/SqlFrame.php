<?php


namespace beacon\core;


class SqlFrame
{
    public string $sql = '';
    public array $args = [];
    public string $type = '';

    /**
     * 构造SQL片段
     * @param string $sql
     * @param array|string|int|float|bool|null $args
     * @param string $type
     */
    public function __construct(string $sql, array|string|int|float|bool|null $args = null, string $type = '')
    {
        $this->sql = trim($sql);
        $this->type = $type;
        if ($args === null || (is_array($args) && count($args) == 0)) {
            return;
        }
        if (!is_array($args)) {
            $this->args = [$args];
        } else {
            $this->args = $args;
        }
    }

    /**
     * 追加片段内容
     * @param string $sql
     * @param array|string|int|float|bool|null $args
     * @return $this
     */
    public function add(string $sql, array|string|int|float|bool|null $args = null): static
    {
        $this->sql .= ' ' . trim($sql);
        if ($args === null || (is_array($args) && count($args) == 0)) {
            return $this;
        }
        if (!is_array($args)) {
            $args = [$args];
        }
        $this->args = array_merge($this->args, $args);
        return $this;
    }


    /**
     * 生成SQL语句
     * @return string
     */
    public function format(): string
    {
        return Mysql::format($this->sql, $this->args);
    }
}