<?php


namespace beacon;


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