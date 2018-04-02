<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/2/25
 * Time: 5:54
 */

namespace beacon;


class SqlItem
{
    public $sql = '';
    public $args = null;

    public function __construct(string $sql, $args = null)
    {
        $this->sql = trim($sql);
        $this->args = $args;
    }

    public function add(string $sql, $args = null)
    {
        $this->sql .= ' ' . $sql;
        if ($args === null || (is_array($args) && count($args) == 0)) {
            return $this;
        }
        if (!is_array($args)) {
            $args = [$args];
        }
        if ($this->args === null) {
            $this->args = $args;
        } else {
            $this->args = array_merge($this->args, $args);
        }
        return $this;
    }

}

class SqlCondition
{
    const WITHOUT_EMPTY = 0;
    const WITHOUT_NULL = 1;
    const WITHOUT_ZERO_LENGTH = 2;
    const WITHOUT_ZERO = 3;
    /**
     * @var SqlItem[]
     */
    private $items = [];
    public $type = 'and';

    public function __construct(string $type = 'and')
    {
        $this->type = 'and';
    }

    public function where($sql = null, $args = null)
    {
        if ($sql === null) {
            return $this;
        }
        if ($sql instanceof SqlCondition) {
            $frame = $sql->getFrame();
            if (!empty($frame['sql'])) {
                if (preg_match('@^(AND|OR)\s+@i', $frame['sql'])) {
                    $frame['sql'] = preg_replace('@^(AND|OR)\s+@i', '', $frame['sql']);
                }
                if ($frame['type'] !== '') {
                    $this->items[] = new SqlItem($frame['type'] . ' (' . $frame['sql'] . ')', $frame['args']);
                } else {
                    $this->items[] = new SqlItem('(' . $frame['sql'] . ')', $frame['args']);
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
        $item = new SqlItem($sql, $args);
        $this->items[] = $item;
        return $this;
    }

    public function search(string $sql, $value, $type = self::WITHOUT_EMPTY, string $format = null)
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
                if ($value === null || strval($value) === '') {
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
        if ($format !== null) {
            $value = preg_replace('@\{0\}@', $value);
        }
        $this->where($sql, $value);
        return $this;
    }

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
        return ['sql' => join(' ', $sqlItems), 'args' => $argItems, 'type' => $this->type];
    }

}
