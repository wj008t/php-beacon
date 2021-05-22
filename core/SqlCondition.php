<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/2/25
 * Time: 5:54
 */

namespace beacon;


class SqlCondition
{
    const WITHOUT_EMPTY = 0;
    const WITHOUT_NULL = 1;
    const WITHOUT_ZERO_LENGTH = 2;
    const WITHOUT_ZERO = 3;
    /**
     * @var SqlFrame[]
     */
    private $items = [];
    public $type = 'and';

    public function __construct(string $type = 'and')
    {
        $this->type = 'and';
    }

    /**
     * 查询条件
     * @param null $sql
     * @param null $args
     * @return $this
     */
    public function where($sql = null, $args = null)
    {
        if ($sql === null) {
            return $this;
        }
        if ($sql instanceof SqlCondition) {
            $frame = $sql->getFrame();
            $frameSql = $frame->sql;
            if (!empty($frameSql)) {
                if (preg_match('@^(AND|OR)\s+@i', $frameSql)) {
                    $frameSql = preg_replace('@^(AND|OR)\s+@i', '', $frameSql);
                }
                if ($frame->type !== '') {
                    $this->items[] = new SqlFrame($frame->type . ' (' . $frameSql . ')', $frame->args, 'where');
                } else {
                    $this->items[] = new SqlFrame('(' . $frameSql . ')', $frame->args, 'where');
                }
            }
            return $this;
        } elseif (is_array($sql) && $args === null) {
            foreach ($sql as $key => $value) {
                if (strpos($key, '?') === false) {
                    $this->where($key . '=?', $value);
                } else {
                    $this->where($key, $value);
                }
            }
            return $this;
        }
        if (!is_string($sql)) {
            return $this;
        }
        $sql = trim($sql);
        if (!isset($sql[0])) {
            return $this;
        }
        $item = new SqlFrame($sql, $args, 'where');
        $this->items[] = $item;
        return $this;
    }

    /**
     * 检索条件,如果值为空 不加入筛选
     * @param string $sql
     * @param $value
     * @param int $type
     * @param string|null $format
     * @return $this
     */
    public function search(string $sql, $value, int $type = self::WITHOUT_EMPTY, string $format = null)
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

        if (!is_array($value) && $format !== null) {
            $value = preg_replace('@\{0\}@', $value);
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
     * 获取代码帧
     * @return SqlFrame
     */
    public function getFrame()
    {
        $sqlItems = [];
        $argItems = [];
        foreach ($this->items as $item) {
            $tempSql = $item->sql;
            $tempArgs = $item->args;
            if (preg_match('@^or\s+@i', $tempSql)) {
                if (count($sqlItems) == 0) {
                    $tempSql = preg_replace('@^or\s+@i', '', $tempSql);
                }
            } else if (preg_match('@^and\s+@i', $tempSql)) {
                if (count($sqlItems) == 0) {
                    $tempSql = preg_replace('@^and\s+@i', '', $tempSql);
                }
            } else {
                if (count($sqlItems) >= 0) {
                    $tempSql = 'and ' . $tempSql;
                }
            }
            $sqlItems[] = $tempSql;
            if (is_array($tempArgs)) {
                $argItems = array_merge($argItems, $tempArgs);
            } else if ($tempArgs !== null) {
                $argItems[] = $tempArgs;
            }

        }
        return new SqlFrame(join(' ', $sqlItems), $argItems, $this->type);
    }

    public function empty()
    {
        $this->items = [];
    }

}
