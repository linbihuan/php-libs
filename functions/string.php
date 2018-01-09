<?php

/**
 * 字符/数字相关函数
 */

/**
 * 获取某个浮点数的 digit，比如 12.39719 返回 5
 *
 * @param  float $number
 * @return int
 */
function get_digits($number)
{
    return strlen(preg_replace('/\d+\./', '', $number));
}

/**
 * 因 number_format 默认参数带来的千分位是逗号的hack
 * 功能和 number_format一致，只是设定了固定的第3，4个参数
 *
 * @param  mixed   $number
 * @param  integer $decimals
 * @param  string  $thousands_sep
 * @return mixed
 */
function num_format($number, $decimals = 0, $thousands_sep = ',')
{
    return number_format($number, $decimals, '.', $thousands_sep);
}

/**
 * 按指定的长度切割字符串
 *
 * @param  string   $string 需要切割的字符串
 * @param  integer  $length 长度
 * @param  string   $suffix 切割后补充的字符串
 * @return string
 */
function str_break($string, $length, $suffix = '')
{
    if (strlen($string) <= $length + strlen($suffix)) {
        return $string;
    }

    $n = $tn = $noc = 0;
    while ($n < strlen($string)) {
        $t = ord($string[$n]);
        if (9 == $t || 10 == $t || (32 <= $t && $t <= 126)) {
            $tn = 1;
            $n++;
            $noc++;
        } elseif (194 <= $t && $t <= 223) {
            $tn = 2;
            $n += 2;
            $noc += 2;
        } elseif (224 <= $t && $t < 239) {
            $tn = 3;
            $n += 3;
            $noc += 2;
        } elseif (240 <= $t && $t <= 247) {
            $tn = 4;
            $n += 4;
            $noc += 2;
        } elseif (248 <= $t && $t <= 251) {
            $tn = 5;
            $n += 5;
            $noc += 2;
        } elseif (252 == $t || 253 == $t) {
            $tn = 6;
            $n += 6;
            $noc += 2;
        } else {
            $n++;
        }
        if ($noc >= $length) {
            break;
        }
    }
    $noc > $length && $n -= $tn;
    $strcut = substr($string, 0, $n);
    if (strlen($strcut) < strlen($string)) {
        $strcut .= $suffix;
    }

    return $strcut;
}

/**
 * 字符串高亮
 *
 * @param  string   $string  需要的高亮的字符串
 * @param  mixed    $keyword 关键字，可以是一个数组
 * @return string
 */
function highlight_keyword($string, $keyword)
{
    $string = (string) $string;

    if ($string && $keyword) {
        if (!is_array($keyword)) {
            $keyword = [$keyword];
        }

        $pattern = [];
        foreach ($keyword as $word) {
            if (!empty($word)) {
                $pattern[] = '(' . str_replace('/', '\/', preg_quote($word)) . ')';
            }
        }

        if (!empty($pattern)) {
            $string = preg_replace(
                '/(' . implode('|', $pattern) . ')/is',
                '<span style="background:#FF0;color:#E00;">\\1</span>',
                $string
            );
        }
    }

    return $string;
}

/**
 * 将 HTML 转换为文本
 *
 * @param  string   $html
 * @return string
 */
function html2txt($html)
{
    $html = trim($html);
    if (empty($html)) {
        return $html;
    }

    $search = ["'<script[^>]*?>.*?</script>'si",
        "'<style[^>]*?>.*?</style>'si",
        "'<[\/\!]*?[^<>]*?>'si",
        "'([\r\n])[\s]+'",
        "'&(quot|#34);'i",
        "'&(amp|#38);'i",
        "'&(lt|#60);'i",
        "'&(gt|#62);'i",
        "'&(nbsp|#160)[;]*'i",
        "'&(iexcl|#161);'i",
        "'&(cent|#162);'i",
        "'&(pound|#163);'i",
        "'&(copy|#169);'i",
        "'&#(\d+);'e",
    ];
    $replace = ["", "", "", "\\1", "\"", "&", "<", ">", " ",
        chr(161), chr(162), chr(163), chr(169), "chr(\\1)"];

    return preg_replace($search, $replace, $html);
}

if (!function_exists('lcwords')) {
    /**
     * Lowercase the first character of each word in a string
     *
     * @param  string   $string
     * @return string
     */
    function lcwords($string)
    {
        $tokens = explode(' ', $string);
        if (!is_array($tokens) || count($tokens) <= 1) {
            return lcfirst($string);
        }

        $result = [];
        foreach ($tokens as $token) {
            $result[] = lcfirst($token);
        }

        return implode(' ', $result);
    }
}

/**
 * 转换为 utf8 编码
 *
 * @param  mixed   $data          需要转换的数据
 * @param  string  $from_encoding 来源编码类型，默认自动检测类型
 * @return mixed
 */
function to_utf8($data, $from_encoding = 'gbk')
{
    return to_encoding($data, 'utf-8', $from_encoding);
}

/**
 * 转换为 gbk 编码
 *
 * @param  mixed   $data          需要转换的数据
 * @param  string  $from_encoding 来源编码类型，默认自动检测类型
 * @return mixed
 */
function to_gbk($data, $from_encoding = 'utf-8')
{
    // 转化成 gbk 存在特殊的繁体中文字符无法识别的情况，这里用gbk的编码
    return to_encoding($data, 'gbk', $from_encoding);
}

/**
 * 支持对多维数组，对象，... 进行编码转换
 * 在不指定来源编码时，由系统自动检测编码类型
 *
 * @link   http://cn2.php.net/manual/zh/function.mb-detect-encoding.php
 * @link   http://cn2.php.net/manual/zh/function.mb-detect-order.php
 *
 * @param  mixed    $data          需要转换的数据
 * @param  string   $to_encoding   目标编码类型
 * @param  string   $from_encoding 来源编码类型，默认自动检测类型
 * @param  mixed    $encoding_list 编码检测类型及顺序
 * @return mixed
 */
function to_encoding($data, $to_encoding, $from_encoding = null,
    $encoding_list = 'UTF-8,GBK,CP936,ISO-8859-1,ASCII') {
    if (is_string($data)) {
        if (null === $from_encoding) {
            $from_encoding = mb_detect_encoding($data, $encoding_list);
        }

        if (strtoupper($to_encoding) !== strtoupper($from_encoding)) {
            $data = mb_convert_encoding($data, $to_encoding, $from_encoding);
        }

    } elseif (is_array($data) || is_object($data)) {
        foreach ($data as $key => &$val) {
            $val = call_user_func_array(__FUNCTION__, [$val, $to_encoding, $from_encoding]);
        }
    }

    return $data;
}

/**
 * 转换驼峰式字符串为下划线风格
 *
 *     uncamel('lowerCamelCase') === 'lower_camel_case'
 *     uncamel('UpperCamelCase') === 'upper_camel_case'
 *     uncamel('ThisIsAString') === 'this_is_a_string'
 *     uncamel('notcamelcase') === 'notcamelcase'
 *     uncamel('lowerCamelCase', ' | ') === 'lower | camel | case'
 *
 * @param  string    $string
 * @param  string    $separator
 * @return string
 */
function uncamel($string, $separator = '_')
{
    return str_replace('_', $separator, Phalcon\Text::uncamelize($string));
}

/**
 * 转换下划线字符串为驼峰式风格
 *
 *     camel('lower_camel_case') === 'lowerCamelCase'
 *     camel('upper_camel_case', true) === 'UpperCamelCase'
 *
 * @param  string   $string
 * @param  string   $upper
 * @return string
 */
function camel($string, $upper = false, $separator = '_')
{
    $string = str_replace($separator, '_', $string);

    return $upper ? Phalcon\Text::camelize($string) : lcfirst(Phalcon\Text::camelize($string));
}

/**
 * RMB 数字转大写
 *
 * @param  string   $num
 * @return string
 */
function number_to_rmb($num)
{
    if ('0' == $num) {
        return '零';
    }

    $chs = ['0', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖'];
    $uni = ['', '拾', '佰', '仟'];
    $exp = ['', '万'];
    $num .= '';
    $res = '';
    for ($i = strlen($num) - 1, $k = 0; $i >= 0; $k++) {
        $str = '';
        // 按照中文读写习惯，每4个字为一段进行转化
        for ($j = 0; $j < 4 && $i >= 0; $j++, $i--) {
            $u   = $num{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
            $str = $chs[$num{$i}] . $u . $str;
        }

        $str = preg_replace('/0+$/', '', $str);   // 去掉末尾的0
        $str = preg_replace('/0+/', '零', $str); // 替换0

        if (!isset($exp[$k])) {
            $exp[$k] = $exp[$k - 2] . '亿'; //构建单位
        }

        $u2  = '' != $str ? $exp[$k] : '';
        $res = $str . $u2 . $res;
    }

    return $res;
}

/**
 * 根据中国身份证号码获得出生日期
 *
 * @param  string   $idNumber
 * @return string
 */
function cn_id_to_birthday($idNumber)
{
    if (strlen($idNumber) != 18) {
        return false;
    }

    return substr($idNumber, 6, 4) . '/' .
    substr($idNumber, 10, 2) . '/' .
    substr($idNumber, 12, 2);
}

/**
 * 根据 索引 返回对应的 map
 * @param  array $maps
 * @param  mix   $index
 * @return mix
 */
function maps_get($maps, $index)
{
    return (is_null($index) || !isset($maps[$index])) ? '-' : $maps[$index];
}

/**
 * 通用影藏方法
 *
 * @param  string   $name
 * @param  string   $hide
 * @return string
 */
function mask($str, $prefix_len=1, $suffix_len=1, $hide = '****')
{
    if (!$str) {
        return '-';
    }

    return mb_substr($str, 0, $prefix_len) . $hide . mb_substr($str, -1*$suffix_len, $suffix_len);
}

/**
 * 隐藏用户名
 *
 * @param  string   $name
 * @param  string   $hide
 * @return string
 */
function mask_name($name, $hide = '****')
{
    if (!$name) {
        return '-';
    }

    return mb_substr($name, 0, 1) . $hide . mb_substr($name, -1, 1);
}

/**
 * 隐藏银行卡号
 *
 * @param  string   $name
 * @param  string   $hide
 * @return string
 */
function mask_bank_card($name, $hide = '****')
{
    if (!$name) {
        return '-';
    }

    return $hide . mb_substr($name, -4, 4);
}

/**
 * 隐藏电话号码
 *
 * @param  string   $phone
 * @param  string   $hide
 * @return string
 */
function mask_phone($phone, $hide = '****')
{
    if (!$phone) {
        return '-';
    }

    $pos = strpos($phone, ' ');

    return mb_substr($phone, 0, (false === $pos ? 3 : $pos + 4))
    . $hide . mb_substr($phone, -4, 4);
}

/**
 * 隐藏 Email
 *
 * @param  string   $email
 * @param  string   $hide
 * @return string
 */
function mask_email($email, $hide = '****')
{
    if (!$email) {
        return '-';
    }

    return mb_substr($email, 0, 2) . $hide . strstr($email, '@');
}

/**
 * 通过键值获取业务分类列表
 *
 * @param  integer  $key
 * @return string
 */
function get_bin_list($key = 0)
{
    $business = [
        '1' => '集合理财',
        '2' => '外汇',
        '4' => '股票',
    ];
    if ($key <= 0 || $key > 7) {
        return '未知类型';
    }
    $temp = 0;
    $str  = '';
    if (array_key_exists($key, $business)) {
        return $business[$key];
    }
    if (3 == $key) {
        $str = $business[1] . ' ' . $business[2];

        return $str;
    }
    if (5 == $key) {
        $str = $business[1] . ' ' . $business[4];

        return $str;
    }
    if (6 == $key) {
        $str = $business[2] . ' ' . $business[4];

        return $str;
    }
    if (7 == $key) {
        $str = $business[1] . ' ' . $business[2] . ' ' . $business[4];

        return $str;
    }
}

function date_to_null($date)
{
    return ('0000-00-00' == $date ? '' : $date);
}

/**
 * 分割手机号码中的国家码
 * @param  string $phone    手机号码
 * @return array  ['c_code' => xxx , 'phone' => 'xxx']
 */
function splitPhone($phone = '')
{

    if ('' == $phone) {
        return false;
    }

    preg_match('/^(\d{2,4})\s+(\d+)/', trim($phone), $out);
    if (empty($out)) {
        // 没有 再进行匹配是否是中国的手机号码
        if (!preg_match('/^(?:1[34578]\d)-?\d{5}(\d{3})$/', $phone)) {
            write_log('phone_failed', $phone . ' is not right');
            return false;
        }

        $arr = [
            'c_code' => '86',
            'phone'  => $phone,
        ];
    } else {
        $arr = [
            'c_code' => $out[1],
            'phone'  => $out[2],
        ];
    }

    return $arr;
}

/**
 * App 版本号 转换 int -> str： e.g. 65535 -> 1.0.0
 * @param  integer
 * @return string    a.b.x
 */
function appVersionIntToStr($int)
{
    $int = intval($int);
    if (-1 == $int) {
        return "所有版本";
    }

    $s    = [];
    $mask = (1 << 8) - 1; // mask: 11111111
    for ($i = 2; $i >= 0; $i--) {
        $s[] = ($int >> ($i * 8)) & $mask;
    }
    return implode('.', $s);
}

function format_name($realname, $default = '-')
{

    if ((string) trim($realname) === '') {
        return $default;
    }

    return $realname;
}

/**
 * 替换字符串中间位置字符为星号
 * @param  [type] $str            [description]
 * @return [type] [description]
 */
function half_replace($str)
{
    $len = strlen($str) / 2;
    return substr_replace($str, str_repeat('*', $len), floor(($len) / 2), $len);
}

/*
 * 数字 向下 保留几位小数
 */
function num_under($num, $bit)
{
    $arr = preg_split('/\./', $num, -1);
    return count($arr) == 1 ? $arr[0] . '.00' : $arr[0] . '.' . substr($arr[1], 0, $bit);
}

/**
 * 获取字符长度
 * @param  [type] $str            [description]
 * @return [type] [description]
 */
function get_mixstring_length($str)
{
    if (empty($str)) {
        return 0;
    }
    $reslen = 0;
    $len    = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $chr = substr($str, $i, 1);
        if (ord($chr) > 127) {
            $i += 2;
            $reslen += 2;
        } else {
            $reslen += 1;

        }
    }
    return $reslen;
}
