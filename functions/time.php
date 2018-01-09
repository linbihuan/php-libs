<?php

/**
 * 日期、时间相关函数
 */

/**
 * 美化时间显示
 *
 * @param  integer  $time
 * @param  integer  $end_time
 * @return string
 */
function time_ago($time, $end_time = null)
{
    $diff = (is_null($end_time) ? time() : $end_time) - $time;

    if ($diff <= 0) {
        return __('Just Now');
    }

    $tokens = [
        31536000 => 'years',
        2592000  => 'months',
        604800   => 'weeks',
        86400    => 'days',
        3600     => 'hours',
        60       => 'mins',
        1        => 'secs',
    ];

    foreach ($tokens as $unit => $text) {
        if ($diff < $unit) {
            continue;
        }

        return __("#n $text", ['#n' => floor($diff / $unit)]);
    }
}

/**
 * 将 gmt date 转为 cn date
 *
 * @param  string    $gmt_date
 * @return [string
 */
function cn_date($gmt_date)
{
    return $gmt_date
    ? date('Y-m-d H:i:s', strtotime($gmt_date) + 8 * 3600)
    : $gmt_date;
}

/**
 * after余时间转为文本
 *
 * @param  integer  $expire_time
 * @return string
 */
function time_remaining($expire_time, $expired = '')
{
    $diff = date_diff(
        date_create(date('Y-m-d H:i:s', $expire_time)),
        date_create(),
        true// 第三个 bool 参数使相差的值为正整数
    );

    $last_secs   = $expire_time - time();
    $last_hours  = ceil($last_secs / 3600);
    $last_mins   = ceil($last_secs / 60);
    $last_days   = $diff->days;
    $last_weeks  = ceil($diff->days / 7);
    $last_months = $diff->y * 12 + $diff->m + ($diff->d > 1 ? 1 : 0);

    if ($last_secs <= 0) {
        return $expired;
    }

    switch (true) {
        case $last_months > 1:
            return __('after #n months', ['#n' => $last_months]);
        case $last_weeks > 1:
            return __('after #n weeks', ['#n' => $last_weeks]);
        case $last_days > 1:
            return __('after #n days', ['#n' => $last_days]);
        case $last_hours > 1:
            return __('after #n hours', ['#n' => $last_hours]);
        case $last_mins > 1:
            return __('after #n mins', ['#n' => $last_mins]);
        case $last_secs > 1:
            return __('after #n secs', ['#n' => $last_secs]);
        default:
            return $expired;
    }
}

/**
 * 计算周期时间
 *
 * @param  int    $start
 * @param  int    $periods 期限
 * @param  string $unit    期限单位
 * @return int
 */
function periods_time($start, $periods, $unit = 'm')
{
    is_numeric($start) || $start = strtotime($start);

    switch (strtolower($unit)) {
        case 'y':
            return strtotime("+$periods years", $start);

        case 'w':
            return strtotime("+$periods weeks", $start);

        case 'm':
            $n    = date('m', $start) + $periods;
            $y    = date('Y', $start) + floor($n / 12);
            $m    = $n % 12;
            $d    = date('d', $start);
            $time = mktime(date('H', $start), date('i', $start), date('s', $start), $m, 1, $y);

            // 防止超过本月最大天数
            $d = min($d - 1, date('d', strtotime('last day of this month', $time)));

            return strtotime("+$d days", $time);

        case 'd':
        default:
            return strtotime("+$periods days", $start);
    }
}

/**
 * 返回 gmt 格式时间戳
 *
 * @param  mixed $str
 * @param  int   $time
 * @return int
 */
function gmtime($str = '', $time = null)
{
    if (is_numeric($str) && $str) {
        $str = date('Y-m-d H:i:s', $str);
    }

    return strtotime($str . ' GMT', $time);
}

/**
 * 将任意时间/日期转换为对应的日期格式
 *
 * @param  string   $format
 * @param  int      $anytime
 * @return string
 */
function to_date($format, $anytime)
{
    if (is_numeric($anytime) && $anytime) {
        $timestamp = $anytime;
    } else {
        $timestamp = strtotime($anytime);
    }

    return $timestamp > strtotime('1970-1-1') ? date($format, $timestamp) : null;
}

/**
 * 比较两个时间, 返回　$datetime　相对　$base　的时间
 * @param  datetime $datetime                 支持时间戳
 * @param  datetime $base　支持时间戳
 * @param  array    $opts
 * @return string
 */
function time_diff($datetime, $base = 'now', $opts = array())
{
    $datetime = is_datetime($datetime) || is_datetime($datetime, 'Y-m-d')
    ? $datetime
    : date('Y-m-d H:i:s', (int) $datetime);
    $base = is_datetime($base) || is_datetime($base, 'Y-m-d')
    ? $base
    : ('now' === $base ? 'now' : date('Y-m-d H:i:s', (int) $base));

    // 容错处理
    if ('1970-01-01 08:00:00' === $datetime || '1970-01-01 08:00:00' === $base) {
        return '';
    }

    $interval = date_create($datetime)->diff(new DateTime($base));

    // $interval->invert　为１表示一个负的时间值
    $suffix = $interval->invert
    ? (isset($opts['ago']) ? $opts['ago'] : '')
    : (isset($opts['after']) ? $opts['after'] : '');

    if ($v = $interval->y >= 1) {
        return $interval->y . __('年') . $suffix;
    }

    if ($v = $interval->m >= 1) {
        return $interval->m . __('个月') . $suffix;
    }

    if ($v = $interval->d >= 1) {
        return $interval->d . __('天') . $suffix;
    }

    if ($v = $interval->h >= 1) {
        return $interval->h . __('小时') . $suffix;
    }

    if ($v = $interval->i >= 1) {
        return $interval->i . __('分') . $suffix;
    }

                                               // 小于１分也返回１分钟
    return $interval->i . __('分') . $suffix; // $interval->s . __('秒') . $suffix;
}

/**
 * 返回时间是星期几？
 *
 * @param  mixed    $time
 * @return string
 */
function week_day($time)
{
    if (!(is_numeric($time) && $time)) {
        $time = strtotime($time);
    }

    $weeks = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    return __($weeks[date('w', $time)]);
}

function check_time($time)
{
    if ((int) $time === 0) {
        return '-';
    }
    return Date('Y-m-d H:i:s', $time);
}

// 根据时间差  返回天数 或者小时数等
function getDateByType($time, $type = 'day')
{
    $num = 0;
    switch ($type) {
        case 'day':
            $num = ($time / (24 * 3600));
            break;
        case 'hours':
            $num = ($time % (24 * 3600)) / 3600;
            break;
        case 'min':
            $num = (($time % (24 * 3600)) % 3600) / 60;
            break;
        case 'seconds':
            $num = (($time % 60) % 60);
            break;
        default:
            break;
    }

    return (int) $num;
}
