<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-11-13
 * Time: 下午11:41
 */

namespace beacon;


interface ViewInterface
{
    public static function instance();

    public function context(Controller $controller);

    public function assign($key, $val = null);

    public function fetch(string $tplName);

    public function display(string $tplName);



}