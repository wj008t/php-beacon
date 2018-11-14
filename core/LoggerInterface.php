<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-11-14
 * Time: 下午12:41
 */

namespace beacon;


interface LoggerInterface
{
    public function info(...$args);

    public function error(...$args);

    public function warn(...$args);

    public function log(...$args);
}