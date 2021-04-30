<?php


namespace beacon\widget;


use beacon\core\App;
use beacon\core\DB;
use beacon\core\DBException;
use beacon\core\Field;

#[\Attribute]
class SelectDialog extends Field
{
    /**
     * 用于兑换文本的方法
     * @var string|array|null
     */
    public string|array|null $textFunc = null;
    public string $textSql = '';

    /**
     * 对话框链接
     * @var string
     */
    public string $url = '';

    /**
     * @param array $args
     */
    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['textFunc']) && is_callable($args['textFunc'])) {
            $this->textFunc = $args['textFunc'];
        }
        if (isset($args['textSql']) && is_string($args['textSql'])) {
            $this->textSql = $args['textSql'];
        }
        if (isset($args['url']) && is_string($args['url'])) {
            $this->url = $args['url'];
        }
    }

    /**
     * 生成的代码
     * @param array $attrs
     * @return string
     * @throws DBException
     */
    protected function code(array $attrs = []): string
    {
        $attrs['yee-module'] = $this->getYeeModule('select-dialog');
        $attrs['type'] = 'hidden';
        $attrs['data-url'] = App::url($this->url);
        $value = $this->getValue();
        if (!empty($value)) {
            if (!empty($this->textFunc) && is_callable($this->textFunc)) {
                $text = call_user_func($this->textFunc, $value);
                $attrs['data-text'] = $text;
            } elseif (!empty($this->textSql)) {
                $row = DB::getRow($this->textSql, $value, \PDO::FETCH_COLUMN);
                if ($row) {
                    $attrs['data-text'] = $row[0];
                } else {
                    $attrs['data-text'] = $value;
                }
            } else {
                $attrs['data-text'] = $value;
            }
        }
        return static::makeTag('input', ['attrs' => $attrs]);
    }
}