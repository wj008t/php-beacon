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
    protected array $_attrs=[
        'class'=>'form-inp multi-dialog',
    ];
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
     * 填值模式
     * @var string
     */
    public int $mode = 1;

    /**
     * 携带参数
     * @var string
     */
    public string $carry = '';

    /**
     * @var string 每项类型
     */
    public string $itemType = 'string';

    public int $width = 0;
    public int $height = 0;
    public string $btnText = '';
    public bool $clearBtn = false;


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
        if (isset($args['mode']) && is_int($args['mode'])) {
            $this->mode = $args['mode'];
            if ($this->mode == 1) {
                $this->itemType = 'array';
            }
        }
        if (isset($args['carry']) && is_string($args['carry'])) {
            $this->carry = $args['carry'];
        }
        if (isset($args['btnText']) && is_string($args['btnText'])) {
            $this->btnText = $args['btnText'];
        }
        if (isset($args['width']) && is_int($args['width'])) {
            $this->width = $args['width'];
        }
        if (isset($args['height']) && is_int($args['height'])) {
            $this->height = $args['height'];
        }
        if (isset($args['clearBtn']) && is_bool($args['clearBtn'])) {
            $this->clearBtn = $args['clearBtn'];
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
        $attrs['data-mode'] = $this->mode;
        if (!empty($this->carry)) {
            $attrs['data-carry'] = $this->carry;
        }
        if (!empty($this->btnText)) {
            $attrs['data-btn-text'] = $this->btnText;
        }
        if ($this->clearBtn) {
            $attrs['data-clear-btn'] = 1;
        }
        if (!empty($this->width)) {
            $attrs['data-width'] = $this->width;
        }
        if (!empty($this->height)) {
            $attrs['data-height'] = $this->height;
        }
        $values = $this->getValue();
        if ($this->mode == 2 && !empty($values)) {
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
                $attrs['data-items'] = $data;
            } elseif (!empty($this->textSql)) {
                $data = [];
                foreach ($values as $val) {
                    $row = DB::getRow($this->textSql, $val, \PDO::FETCH_NUM);
                    if ($row) {
                        $text = $row[0];
                    } else {
                        $text = $val;
                    }
                    $data[] = ['value' => $val, 'text' => $text];
                }
                $attrs['data-items'] = $data;
            } else {
                $data = [];
                foreach ($values as $val) {
                    $data[] = ['value' => $val, 'text' => $val];
                }
                $attrs['data-items'] = $data;
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
        if ($this->mode == 1) {
            $this->itemType = 'array';
        }
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
        if ($this->mode == 1) {
            $this->itemType = 'array';
        }
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