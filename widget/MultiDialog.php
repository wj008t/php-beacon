<?php


namespace beacon\widget;


use beacon\core\App;
use beacon\core\DB;
use beacon\core\DBException;
use beacon\core\Field;
use beacon\core\Request;
use beacon\core\Util;

/**
 * 多选对话框
 * Class MultiDialog
 * @package beacon\widget
 */
#[\Attribute]
class MultiDialog extends Field
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
     * 携带参数
     * @var string
     */
    public string $carry = '';

    /**
     * @var string 每项类型
     */
    public string $itemType = '';

    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['textFunc']) && is_callable($args['textFunc'])) {
            $this->textFunc = $args['textFunc'];
        }
        if (isset($args['textSql']) && is_string($args['textSql'])) {
            $this->textSql = $args['textSql'];
        }
        if (isset($args['itemType']) && is_string($args['itemType'])) {
            $this->itemType = $args['itemType'];
        }
        if (isset($args['url']) && is_string($args['url'])) {
            $this->url = $args['url'];
        }
        if (isset($args['carry']) && is_string($args['carry'])) {
            $this->carry = $args['carry'];
        }
    }

    /**
     * 生成代码
     * @param array $attrs
     * @return string
     * @throws DBException
     */
    protected function code(array $attrs = []): string
    {
        $attrs['yee-module'] = $this->getYeeModule('multiple-dialog');
        $attrs['type'] = 'hidden';
        $attrs['data-url'] = App::url($this->url);
        $values = $this->getValue();
        if (!empty($values)) {
            if (is_string($values) && Util::isJson($values)) {
                $values = json_decode($values, true);
            }
            if (!is_array($values)) {
                $values = [];
            }
            if (!empty($this->textFunc) && is_callable($this->textFunc)) {
                $data = [];
                foreach ($values as $val) {
                    $text = call_user_func($this->textFunc, $val);
                    $data[] = ['value' => $val, 'text' => $text];
                }
                $attrs['data-text'] = $data;
            } elseif (!empty($this->textSql)) {
                $data = [];
                foreach ($values as $val) {
                    $row = DB::getRow($this->textSql, $val, \PDO::FETCH_COLUMN);
                    if ($row) {
                        $text = $row[0];
                    } else {
                        $text = $val;
                    }
                    $data[] = ['value' => $val, 'text' => $text];
                }
                $attrs['data-text'] = $data;
            } else {
                $data = [];
                foreach ($values as $val) {
                    $data[] = ['value' => $val, 'text' => $val];
                }
                $attrs['data-text'] = $data;
            }
        }
        return static::makeTag('input', ['attrs' => $attrs]);
    }

    /**
     * 从表单拿值
     * @param array $param
     * @return array
     */
    public function fromParam(array $param = []): array
    {
        $name = $this->boxName;
        $values = Request::lookType($param, $name, 'array');
        return Util::mapItemType($values, $this->itemType);
    }

    /**
     * 从数据库拿值
     * @param array $data
     * @return array
     */
    public function fromData(array $data = []): array
    {
        $values = isset($data[$this->name]) ? $data[$this->name] : '';
        if (is_string($values) && Util::isJson($values)) {
            $values = json_decode($values, true);
        }
        if (is_array($values)) {
            return Util::mapItemType($values, $this->itemType);
        }
        return [];
    }

}