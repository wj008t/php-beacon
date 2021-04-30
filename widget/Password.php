<?php


namespace beacon\widget;


use beacon\core\Field;

#[\Attribute]
class Password extends Field
{
    public string|array|null $encodeFunc = 'md5';

    protected mixed $encodeValue = null;

    public function setting(array $args)
    {
        parent::setting($args);
        if (isset($args['encodeFunc']) && is_callable($args['encodeFunc'])) {
            $this->encodeFunc = $args['encodeFunc'];
        }
    }

    protected function code(array $attrs = []): string
    {
        $attrs['type'] = 'password';
        $attrs['value'] = null;
        return static::makeTag('input', ['attrs' => $attrs]);
    }

    /**
     * 加入数据库时
     * @param array $data
     */
    public function joinData(array &$data = [])
    {
        $encodeFunc = $this->encodeFunc;
        $value = $this->getValue();
        if (!empty($value) && $this->encodeValue !== $value && $encodeFunc !== null && is_callable($encodeFunc)) {
            $this->encodeValue = call_user_func($encodeFunc, $value);
            $data[$this->name] = $this->encodeValue;
            return;
        }
        if (empty($value) && !empty($this->encodeValue)) {
            return;
        }
        $data[$this->name] = $value;
    }

    /**
     * 从数据库来的
     * @param array $data
     * @return mixed
     */
    public function fromData(array $data = []): mixed
    {
        $value = isset($data[$this->name]) ? $data[$this->name] : null;
        $this->encodeValue = $value;
        return $value;
    }

}