<?php

/**
 * 验证函数
 */

/**
 * Email格式检查 (支持验证host有效性)
 *
 * @param  string  $email
 * @param  boolean $testMX
 * @return boolean
 */
function is_email($email, $testMX = false)
{
    if (!is_string($email)) {
        return false;
    }
    $pattern = '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})$/iD';
    if (preg_match($pattern, $email)) {
        if ($testMX) {
            list(, $domain) = explode("@", $email);

            return getmxrr($domain, $mxrecords);
        }

        return true;
    }

    return false;
}

/**
 * 检查是否效的 url
 *
 * @param  string  $url
 * @return boolean
 */
function is_url($url)
{
    return (bool) preg_match('/^https?:\/\/([a-z0-9\-]+\.)+[a-z]{2,3}([a-z0-9_~#%&\/\'\+\=\:\?\.\-])*$/i', $url);
}

/**
 * 验证一个日期是否合法
 *
 * @param  datetime $date
 * @return bool
 */
function is_date($date)
{
    return is_datetime($date, 'Y-m-d') || is_datetime($date, 'Y/m/d') || is_datetime($date, 'm/d/Y');
}

/**
 * 验证一个时间串是否合法
 *
 * @param  datetime $datetime
 * @param  string   $format
 * @return bool
 */
function is_datetime($datetime, $format = 'Y-m-d H:i:s')
{
    // DateTime::createFromFormat(PHP >= 5.3.0)
    $d = DateTime::createFromFormat($format, $datetime);

    return ($d && $d->format($format) === $datetime);
}

/**
 * 是否中文字符 (包括全角字符)
 *
 * @param  string  $str
 * @return boolean
 */
function is_chinese($str)
{
    return (bool) preg_match('/[\x{4E00}-\x{9FA5}\x{FE30}-\x{FFA0}\x{3000}-\x{3039}]/u', $str);
}

/**
 * 是否有效的密码
 *
 * @param  string  $str
 * @return boolean
 */
function is_password($str)
{
    return strlen($str) >= 6 && preg_match('/[a-z]/i', $str) && preg_match('/\d/', $str);
}

/**
 * 是否有效的电话号码
 *
 *     var_dump([
 *         is_phone_number('86 18603038502'),
 *         is_phone_number('+86 18603038502'),
 *         is_phone_number('18603038502'),
 *         is_phone_number('0755-86565654'),
 *         is_phone_number('86-18603038502'),
 *     ]);
 */
function is_phone_number($phone)
{
    return (bool) preg_match("/^[+]?(\(\d+\)[-]?)?(\d+[-]?)*\d+$/", str_replace(' ', '-', $phone));
}

/**
 * 是否中国手机号码
 *
 * @param  string  $mobile
 * @return boolean
 */
function is_cn_mobile($mobile)
{
    // 2861111 开头的为伪号码，应付某些特殊需求
    return (bool) preg_match('/^(1\d{10})$|(2861111\d{4})$/', $mobile);
}

/**
 * 是否是中国身份证
 * @param  string  $id
 * @return boolean
 */
function is_cn_id($id)
{
    return (bool) preg_match('/(^\d{15}$)|(^\d{17}([0-9]|X)$)/', $id);
}

/**
 * 是否海外手机号码
 *
 * @param  string  $mobile
 * @return boolean
 */
function is_foreign_mobile($mobile)
{
    return (bool) preg_match('/^\d+ \d+$/', $mobile);
}

function is_num($string)
{
    if ($string === null) {
        return 0;
    }
    return preg_match('/^[\+|\-]?\d+(\.\d+)?$/', $string);
}
