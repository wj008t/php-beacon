<?php


namespace beacon;


class SqlFrame
{
    public $sql = '';
    public $args = null;
    public $type = '';

    public function __construct(string $sql, $args = null, $type = '')
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

    public function add(string $sql, $args = null)
    {
        $this->sql .= ' ' . $sql;
        if ($args === null || (is_array($args) && count($args) == 0)) {
            return $this;
        }
        if (!is_array($args)) {
            $args = [$args];
        }
        if ($this->args == null) {
            $this->args = $args;
        } else {
            $this->args = array_merge($this->args, $args);
        }
        return $this;
    }

    public function format()
    {
        return Mysql::format($this->sql, $this->args);
    }
}