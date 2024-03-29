<?php


namespace beacon\widget;


use beacon\core\App;
use beacon\core\DB;
use beacon\core\DBException;
use beacon\core\Field;
use beacon\core\Util;

#[\Attribute]
class SelectDialog extends Field
{
    protected array $_attrs = [
        'class' => 'form-inp select-dialog',
    ];
    /**
     * 用于兑换文本的方法
     * @var string|array|null
     */
    public string|array|null $textFunc = null;
    public string $textSql = '';
    public string $text = '';

    /**
     * 对话框链接
     * @var string
     */
    public string $url = '';
    public int $width = 0;
    public int $height = 0;
    public string $btnText = '';
    public bool $clearBtn = false;
    /**
     * 携带参数
     * @var string
     */
    public string $carry = '';

    /**
     * @param array $args
     */
    public function setting(array $args): void
    {
        parent::setting($args);
        if (isset($args['textFunc']) && is_callable($args['textFunc'])) {
            $this->textFunc = $args['textFunc'];
        }
        if (isset($args['textSql']) && is_string($args['textSql'])) {
            $this->textSql = $args['textSql'];
        }
        if (isset($args['text']) && is_string($args['text'])) {
            $this->text = $args['text'];
        }
        if (isset($args['url']) && is_string($args['url'])) {
            $this->url = $args['url'];
        }
        if (isset($args['carry']) && is_string($args['carry'])) {
            $this->carry = $args['carry'];
        }
        if (isset($args['btnText']) && is_string($args['btnText'])) {
            $this->btnText = $args['btnText'];
        }
        if (isset($args['clearBtn']) && is_bool($args['clearBtn'])) {
            $this->clearBtn = $args['clearBtn'];
        }
        if (isset($args['width']) && is_int($args['width'])) {
            $this->width = $args['width'];
        }
        if (isset($args['height']) && is_int($args['height'])) {
            $this->height = $args['height'];
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
        if (!empty($this->carry)) {
            $attrs['data-carry'] = $this->carry;
        }
        if (!empty($this->btnText)) {
            $attrs['data-btn-text'] = $this->btnText;
        }
        if (!empty($this->width)) {
            $attrs['data-width'] = $this->width;
        }
        if (!empty($this->height)) {
            $attrs['data-height'] = $this->height;
        }
        if ($this->clearBtn) {
            $attrs['data-clear-btn'] = true;
        }
        if ($this->text !== '') {
            $attrs['data-text'] = $this->text;
        }
        $value = $this->getValue();
        if (is_array($value) && count($value) == 0) {
            $attrs['value'] = '';
        }
        $mode = 2; //非数组模式
        $typeMap = Util::typeMap($this->varType);
        if (isset($typeMap['array'])) {
            $mode = 1; //数组模式
        }
        $attrs['data-mode'] = $mode;
        if ($mode == 2 && $this->text === '') {
            if (!empty($value) && !is_array($value)) {
                if (!empty($this->textFunc) && is_callable($this->textFunc)) {
                    $text = call_user_func($this->textFunc, $value);
                    $attrs['data-text'] = $text;
                } elseif (!empty($this->textSql)) {
                    $row = DB::getRow($this->textSql, $value, \PDO::FETCH_NUM);
                    if ($row) {
                        $attrs['data-text'] = $row[0];
                    } else {
                        $attrs['data-text'] = '';
                    }
                } else {
                    $attrs['data-text'] = $value;
                }
            } else {
                $attrs['data-text'] = '';
            }
        }
        return static::makeTag('input', ['attrs' => $attrs]);
    }
}