<?php

/**
 * 定义array开头的方法,即一些数组相关的操作
 */

/**
 * 递归地合并一个或多个数组(不同于 array_merge_recursive)
 *
 * @return array
 */
function array_merge_deep()
{
    $a = func_get_args();
    for ($i = 1; $i < count($a); $i++) {
        foreach ($a[$i] as $k => $v) {
            if (isset($a[0][$k])) {
                if (is_array($v)) {
                    if (is_array($a[0][$k])) {
                        $a[0][$k] = array_merge_deep($a[0][$k], $v);
                    } else {
                        $v[]      = $a[0][$k];
                        $a[0][$k] = $v;
                    }
                } else {
                    $a[0][$k] = is_array($a[0][$k]) ? array_merge($a[0][$k], [$v]) : $v;
                }
            } else {
                $a[0][$k] = $v;
            }
        }
    }

    return $a[0];
}

/**
 * 移除数组中的空值
 *
 * @param  array   $array
 * @return array
 */
function array_remove_empty(array $array)
{
    return array_filter($array, function ($val) {
        return !is_null($val);
    });
}

/**
 * 用回调函数，根据数组键&值，过滤数组中的单元
 *
 * @param  array   $array
 * @param  mixed   $callback
 * @return array
 */
function array_filter_full(array $array, $callback)
{
    if (!is_callable($callback)) {
        trigger_error(__FUNCTION__ . '() expects parameter 2 to be a valid callback', E_USER_ERROR);
    }

    return $array = array_filter($array, function ($val) use (&$array, $callback) {
        $key = key($array);
        next($array);

        return (bool) $callback($key, $val);
    });
}

/**
 * 将数据转换为字符，并在同一行输出
 *
 * @param  array    $array
 * @param  string   $separator
 * @return string
 */
function array_join_inline(array $array, $separator = ', ')
{
    $tmp = [];
    foreach ($array as $key => $val) {
        $tmp[] = "$key: " . (is_array($val) ? array_join_inline($val, $separator) : $val);
    }

    return implode($separator, $tmp);
}

/**
 * 使用一个二维数组的某一个列进行分组
 * @example
 *      从一个二维数组中 选取指定字段并返回 如：
 *      $a = array(
 *          'a'=>   array('msg_id' => 111, 'content' => 'xxx'),
 *          'b'=>   array('msg_id' => 222, 'content' => 'yyy'),
 *          'c'=>   array('msg_id' => 333, 'content' => 'xxx'),
 *      );
 *      array_group($a, 'content') 返回:
 *      Array (
 *          [xxx] => array(
 *              array('msg_id' => 111, 'content' => 'xxx'),
 *              array('msg_id' => 333, 'content' => 'xxx')
 *          )
 *          [yyy] =>  array(
 *              array('msg_id' => 222, 'content' => 'yyy')
 *          )
 *      )
 * @param  $ary     array
 * @param  $key     string
 * @return array
 */
function array_group($a, $by_column)
{
    $r = [];
    if (!is_array($a)) {
        return $r;
    }

    foreach ($a as $v) {
        if (!isset($v[$by_column])) {
            continue;
        }

        $r[$v[$by_column]][] = $v;
    }

    return $r;
}

/**
 * 使用一个二维数组中的某一个key作键值，返回一个新的数组
 * @example
 *      从一个二维数组中 选取指定字段并返回 如：
 *      $a = array(
 *          'a'=>   array('msg_id' => 111, 'content' => 'xxx'),
 *          'b'=>   array('msg_id' => 222, 'content' => 'yyy'),
 *          'c'=>   array('msg_id' => 333, 'content' => 'zzz'),
 *      );
 *      array_using_key($a, 'content') 返回:
 *      Array ([xxx] => ... [yyy] => ... [zzz] => ... )
 * @param  $ary     array
 * @param  $key     string
 * @return array
 */
function array_using_key($ary, $key)
{
    $tmp = [];
    if (!is_array($ary)) {
        return $tmp;
    }

    foreach ($ary as $r) {
        if (isset($r[$key])) {
            $tmp[$r[$key]] = $r;
        }

    }

    return $tmp;
}

/**
 * 从一个二维数组中 选取指定字段并返回 如：
 * $a = array(
 *  'a'=>   array('msg_id' => 111, 'content' => 'xxx'),
 *  'b'=>   array('msg_id' => 222, 'content' => 'yyy'),
 *  'c'=>   array('msg_id' => 333, 'content' => 'zzz'),
 * );
 * array_pick($a, 'content') 返回:
 * Array ([a] => xxx [b] => yyy [c] => zzz )
 *
 * @param  $array   要选取的数组
 * @param  $field   要选取的字段
 * @return array
 */
function array_pick($array, $field)
{
    if (!is_array($array)) {
        return [];
    }

    $new = [];
    foreach ($array as $k => $a) {
        if (is_array($a) && isset($a[$field])) {
            $new[$k] = $a[$field];
        }

    }

    return $new;
}

/**
 * 从数组中获取值，如果未设定时，返回默认值
 *
 * @param  array|object $array
 * @param  string       $name
 * @param  mixed        $default
 * @return mixed
 */
function array_get($array, $name, $default = null, $empty=false)
{
    if (is_array($array) && isset($array[$name])) {
        return ($empty && ! $array[$name]) ? $default : $array[$name];
    } elseif (is_object($array) && isset($array->$name)) {
        return ($empty && ! $array->$name) ? $default : $array->$name;
    }

    return $default;
}
function A($array, $name, $default = null, $empty = false)
{
    return array_get($array, $name, $default, $empty);
}

/**
 * 对一个二维数组进行排序
 * @param  $array     array  一个二维的数组(以引用的方式传入)
 * @param  $column    string 按那一列进行排序
 * @param  $sort_type int    排序类型 降序: SORT_DESC 或 3 升序: SORT_ASC 或 4
 * @return null
 */
function array_sort_2d(&$array, $column, $sort_type = SORT_DESC)
{
    if (!is_array($array)) {
        return $array;
    }

    $columns = [];
    // 取得列的列表
    foreach ($array as $key => $row) {
        $columns[$key] = $row[$column];
    }
    array_multisort($columns, $sort_type, $array);
}

/**
 * 对一个二维数组的某一列执行相加操作
 * @param  $array  array  一个二维的数组
 * @param  $column string 要累加的那一列
 * @return float
 */
function array_sum_2d($array, $key)
{
    $res = 0;
    foreach (is_array($array) ? $array : [] as $a) {
        $res += (isset($a[$key]) ? $a[$key] : 0);
    }

    return $res;
}

/**
 * 对一个二维数组的某一列执行执行重命名
 * @param  $array    array  一个二维的数组
 * @param  $from     string 原始列名
 * @param  $to       string 重命名后的列名
 * @return array()
 */
function array_rename_2d($array, $from, $to)
{
    if (!is_array($array)) {
        return [];
    }

    foreach ($array as $i => $a) {
        if (isset($a[$from])) {
            $array[$i][$to] = $a[$from];
            unset($array[$i][$from]);
        }
    }

    return $array;
}

/**
 * 对一个二维数组的选择某一些特殊的列
 * @param  $array  array 一个二维的数组
 * @param  $fields mixed 要要抓取的列
 * @return array
 */
function array_select_2d($array, $fields)
{
    if (!is_array($array)) {
        return [];
    }

    $fields = is_array($fields) ? $fields : explode(',', $fields);
    $fields = array_flip($fields);

    foreach ($array as $i => $a) {
        foreach (_a($a) as $k => $v) {
            if (!isset($fields[$k])) {
                unset($array[$i][$k]);
            }

        }
    }

    return $array;
}

/**
 * 将一维数组转换为查询字符串
 *
 * @param  array    $array
 * @param  string   $separator
 * @return string
 */
function array_to_query(array $array)
{
    $tmp = [];
    foreach ($array as $key => $val) {
        $tmp[] = "{$key}={$val}";
    }

    return implode('&', $tmp);
}
