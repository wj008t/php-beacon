<?php


namespace beacon\core;


class SqlRaw
{
    public string $sql;
    public mixed $args;

    public function __construct(string $sql, mixed $args = null)
    {
        $this->sql = $sql;
        $this->args = $args;
    }

    public function format(): string
    {
        return Mysql::format($this->sql, $this->args);
    }
}