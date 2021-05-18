<?php


namespace beacon\core;


class DBException extends \Exception
{
    protected string $detail = '';

    public function setDetail(string $detail = '')
    {
        $this->detail = $detail;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }
}