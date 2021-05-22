<?php


namespace beacon\core;

class SqlCondition
{
    const WITHOUT_EMPTY = 0;
    const WITHOUT_NULL = 1;
    const WITHOUT_ZERO_LENGTH = 2;
    const WITHOUT_ZERO = 3;

    /**
     * @var SqlFrame[]
     */
    protected array $items = [];
    public string $type = 'and';

    public function __construct(string $type = 'and')
    {
        $this->type = $type;
    }

    /**
     * 查询条件
     * @param string|SqlCondition $sql
     * @param mixed $args
     * @return $this
     */
    public function where(string|SqlCondition $sql = '', array|string|int|float|bool|null $args = null): static
    {
        if ($sql instanceof SqlCondition) {
            $frame = $sql->getFrame();
            if (!empty($frame->sql)) {
                $_sql = $frame->sql;
                $_args = $frame->args;
                if (preg_match('@^(and|or)\s+@i', $_sql)) {
                    $_sql = preg_replace('@^(and|or)\s+@i', '', $_sql);
                }
                if ($frame->type !== '') {
                    $this->items[] = new SqlFrame($frame->type . ' (' . $_sql . ')', $_args, 'where');
                } else {
                    $this->items[] = new SqlFrame('(' . $_sql . ')', $_args, 'where');
                }
            }
            return $this;
        }
        $sql = trim($sql);
        if (empty($sql)) {
            return $this;
        }
        $item = new SqlFrame($sql, $args, 'where');
        $this->items[] = $item;
        return $this;
    }

    /**
     * @param string $sql
     * @param array|string|int|float|bool|null $value
     * @param int $type
     * @return $this
     */
    public function search(string $sql, array|string|int|float|bool|null $value, int $type = self::WITHOUT_EMPTY): static
    {
        switch ($type) {
            case self::WITHOUT_EMPTY:
                if (empty($value)) {
                    return $this;
                }
                break;
            case self::WITHOUT_NULL:
                if ($value === null) {
                    return $this;
                }
                break;
            case self::WITHOUT_ZERO_LENGTH:
                if (is_array($value)) {
                    if (count($value) == 0) {
                        return $this;
                    }
                } else if ($value === null || strval($value) === '') {
                    return $this;
                }
                break;
            case self::WITHOUT_ZERO:
                if ($value === '0' || (is_numeric($value) && floatval($value) == 0) || $value === 0 || $value === false || $value === null) {
                    return $this;
                }
                break;
            default:
                break;
        }
        //用于 in not in
        if (substr_count($sql, '[?]') == 1 && is_array($value)) {
            if (count($value) > 0) {
                $temp = [];
                foreach ($value as $item) {
                    $temp[] = '?';
                }
                $sql = str_replace('[?]', join(',', $temp), $sql);
                $this->where($sql, $value);
            }
            return $this;
        }
        //解析多个?
        $maxCount = substr_count($sql, '?');
        if ($maxCount > 1) {
            $temp = [];
            for ($i = 0; $i < $maxCount; $i++) {
                $temp[] = $value;
            }
            $value = $temp;
        }
        $this->where($sql, $value);
        return $this;
    }

    /**
     * 获取查询帧数据
     * @return SqlFrame
     */
    public function getFrame(): SqlFrame
    {
        $sqlItems = [];
        $argItems = [];
        foreach ($this->items as $item) {
            $tempSql = $item->sql;
            $tempArgs = $item->args;
            if (preg_match('@^(or|and)\s+@i', $tempSql)) {
                if (count($sqlItems) == 0) {
                    $tempSql = preg_replace('@^(or|and)\s+@i', '', $tempSql);
                }
            } else {
                if (count($sqlItems) >= 0) {
                    $tempSql = 'and ' . $tempSql;
                }
            }
            $sqlItems[] = $tempSql;
            if (count($tempArgs) > 0) {
                $argItems = array_merge($argItems, $tempArgs);
            }
        }
        return new SqlFrame(join(' ', $sqlItems), $argItems, $this->type);
    }

    /**
     * 清空查询条件
     */
    public function empty(): static
    {
        $this->items = [];
        return $this;
    }

}