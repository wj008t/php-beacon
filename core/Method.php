<?php


namespace beacon\core;

#[\Attribute]
class Method
{

    const GET = 1;
    const POST = 2;
    const PUT = 4;
    const DELETE = 8;

    public int $method = 1;
    public string $act = '';
    public string $contentType = '';

    public function __construct(string $act, int $method = 1, string $contentType = '')
    {
        $this->act = $act;
        $this->method = $method;
        $this->contentType = $contentType;

    }

    public function auth(string $act, string $method): bool
    {
        if (!empty($this->contentType)) {
            Request::setContentType($this->contentType);
        }
        if ($act != $this->act) {
            return false;
        }
        $cMethod = match ($method) {
            'GET' => 1,
            'POST' => 2,
            'PUT' => 4,
            'DELETE' => 8,
        };
        if (($cMethod & $this->method) > 0) {
            return true;
        }
        return false;
    }
}