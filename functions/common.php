<?php

/**
 * 公共函数文件
 */

/**
 * Email格式检查 (支持验证host有效性)
 *
 * @param  string    $email
 * @return boolean
 */
function is_email($email)
{
    return (bool) preg_match(
        '/^([_a-z0-9+-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i',
        $email
    );
}

/**
 * 判断一个字符串是否都有数字组成
 *
 * @param  string    $str
 * @return boolean
 */
function is_pure_num($str)
{
    return preg_match('/^\d+$/', $str);
}

/**
 * 检查是否效的 url, 只检查 https 和 http 两种
 *
 * @param  string    $url
 * @return boolean
 */
function is_url($url)
{
    return preg_match('/^https?:\/\//i', filter_var($url, FILTER_VALIDATE_URL));
}

/**
 * 验证一个时间串是否合法
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
 * 验证一个串是否为时间戳
 * @param  str    $str
 * @return bool
 */
function is_timestamp($str)
{
    return is_pure_num($str) &&
    $str >= 0 &&        // 1970-01-01 08:00:00
    $str <= 2524579199; // 2049-12-31 23:59:59
}

/**
 * 是否中文字符 (包括全角字符)
 *
 * @param  string    $str
 * @return boolean
 */
function is_chinese($str)
{
    return (boolean) preg_match('/[\x{4E00}-\x{9FA5}\x{FE30}-\x{FFA0}\x{3000}-\x{3039}]/u', $str);
}

/**
 * 因 number_format 默认参数带来的千分位是逗号的hack
 * 功能和 number_format一致，只是设定了固定的第3，4个参数
 *
 * @param  mixed   $number
 * @param  integer $decimals
 * @return mixed
 */
function num_format($number, $decimals = 0)
{
    return number_format($number, $decimals, '.', '');
}

/**
 * 生成随机字符串
 *
 * @param  int      $len
 * @param  string   $base
 * @return string
 */
function random_str($len = 5, $base = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
{
    return substr(str_shuffle($base), 0, abs($len));
}

// 把一个utf8字符串转化为html实体表达方式, 如 中华 变为 &#20013;&#21326;
function to_entities($str)
{
    // Note that `mb_convert_encoding($val, 'HTML-ENTITIES')` does not escape '\'', '"', '<', '>', or '&'.
    return mb_convert_encoding($str, 'HTML-ENTITIES', 'utf-8');
}

// $from_encoding 若使用 gbk 存在特殊的繁体中文字符无法识别的情况，这里默认用gbk的编码
function to_utf8($str, $from_encoding = 'gbk')
{
    return mb_convert_encoding($str, 'utf-8', $from_encoding);
}

function to_gbk($str, $from_encoding = 'utf-8')
{
    // 转化成 gbk 存在特殊的繁体中文字符无法识别的情况，这里用gbk的编码
    return mb_convert_encoding($str, 'gbk', $from_encoding);
}

function cutstr($str, $start, $len, $suffix = '')
{
    $charset = 'utf-8';
    if (mb_strlen($str, $charset) <= $len) {
        return $str;
    }

    return mb_substr($str, $start, $len, $charset) . $suffix;
}

function utf8_len($str)
{
    return mb_strlen($str, 'utf-8');
}

// 把一个对象结构递归变成一数组结构
function o2a($d)
{
    if (is_object($d)) {
        if (method_exists($d, 'getArrayCopy')) {
            $d = $d->getArrayCopy();
        } elseif (method_exists($d, 'getArrayIterator')) {
            $d = $d->getArrayIterator()->getArrayCopy();
        } elseif (method_exists($d, 'toArray')) {
            $d = $d->toArray();
        } else
        // Gets the properties of the given object
        // with get_object_vars function
        {
            $d = get_object_vars($d);
        }

    }

    /*
     * Return array converted to object
     * Using __FUNCTION__ (Magic constant)
     * for recursive call
     */
    if (is_array($d)) {
        return array_map(__FUNCTION__, $d);
    }

    // Return array
    return $d;
}

if (!function_exists('http_build_url')) {
    define('HTTP_URL_REPLACE', 1);          // Replace every part of the first URL when there's one of the second URL
    define('HTTP_URL_JOIN_PATH', 2);        // Join relative paths
    define('HTTP_URL_JOIN_QUERY', 4);       // Join query strings
    define('HTTP_URL_STRIP_USER', 8);       // Strip any user authentication information
    define('HTTP_URL_STRIP_PASS', 16);      // Strip any password authentication information
    define('HTTP_URL_STRIP_AUTH', 32);      // Strip any authentication information
    define('HTTP_URL_STRIP_PORT', 64);      // Strip explicit port numbers
    define('HTTP_URL_STRIP_PATH', 128);     // Strip complete path
    define('HTTP_URL_STRIP_QUERY', 256);    // Strip query string
    define('HTTP_URL_STRIP_FRAGMENT', 512); // Strip any fragments (#identifier)
    define('HTTP_URL_STRIP_ALL', 1024);     // Strip anything but scheme and host

    /**
     * Build an URL
     * The parts of the second URL will be merged into the first according to the flags argument.
     *
     * @param mixed   (Part(s) of) an URL in form of a string or associative array like parse_url() returns
     * @param mixed   Same     as the first argument
     * @param integer A        bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
     * @param array   If       set, it will be filled with the parts of the composed url like parse_url() would return
     */
    function http_build_url($url, $parts = [], $flags = HTTP_URL_REPLACE, &$new_url = false)
    {
        $keys = ['user', 'pass', 'port', 'path', 'query', 'fragment'];

        // HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
        if ($flags & HTTP_URL_STRIP_ALL) {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
            $flags |= HTTP_URL_STRIP_PORT;
            $flags |= HTTP_URL_STRIP_PATH;
            $flags |= HTTP_URL_STRIP_QUERY;
            $flags |= HTTP_URL_STRIP_FRAGMENT;
        }
        // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
        elseif ($flags & HTTP_URL_STRIP_AUTH) {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
        }

        // Parse the original URL
        $parse_url = parse_url($url);

        // Scheme and Host are always replaced
        if (isset($parts['scheme'])) {
            $parse_url['scheme'] = $parts['scheme'];
        }

        if (isset($parts['host'])) {
            $parse_url['host'] = $parts['host'];
        }

        // (If applicable) Replace the original URL with it's new parts
        if ($flags & HTTP_URL_REPLACE) {
            foreach ($keys as $key) {
                if (isset($parts[$key])) {
                    $parse_url[$key] = $parts[$key];
                }

            }
        } else {
            // Join the original URL path with the new path
            if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
                if (isset($parse_url['path'])) {
                    $parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
                } else {
                    $parse_url['path'] = $parts['path'];
                }

            }

            // Join the original query string with the new query string
            if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
                if (isset($parse_url['query'])) {
                    $parse_url['query'] .= '&' . $parts['query'];
                } else {
                    $parse_url['query'] = $parts['query'];
                }

            }
        }

        // Strips all the applicable sections of the URL
        // Note: Scheme and Host are never stripped
        foreach ($keys as $key) {
            if ($flags & (int) constant('HTTP_URL_STRIP_' . strtoupper($key))) {
                unset($parse_url[$key]);
            }

        }

        $new_url = $parse_url;

        return
            ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '') .
            ((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') . '@' : '') .
            ((isset($parse_url['host'])) ? $parse_url['host'] : '') .
            ((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '') .
            ((isset($parse_url['path'])) ? $parse_url['path'] : '') .
            ((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '') .
            ((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
        ;
    }
}

//hide some str
function hide_str($str)
{
    $result = $str;
    if (is_email($str)) {
        $str = preg_replace('/(\w+)(\w{3})@(.*)/', '${1}***@${3}', $str);
        if ($str) {
            $result = $str;
        }
    } else {
        $str = preg_replace('/(\d{3})(\d{4})(\d{4})/', '${1}****${3}', $str);
        if ($str) {
            $result = $str;
        }
    }

    return $result;
}

/**
 * 返回纯数字
 * @param  $str
 * @return string
 */
function pure_num($str)
{
    return preg_replace('/[^\d]/', '', $str);
}

/**
 * 判断两个浮点数是否相等
 * @param  float  $num1
 * @param  float  $num2
 * @param  float  $diff
 * @return bool
 */
function float_eq($num1, $num2, $diff = 0.000001)
{
    return abs($num1 - $num2) < $diff;
}

// 是否中国手机号码
function is_cn_phone($phone)
{
    // 2861111 开头的为伪号码，应付某些特殊需求
    return preg_match('/^(1\d{10})$|(2861111\d{4})$/', $phone);
}

function is_foreign_phone($phone)
{
    return preg_match('/^\d+ \d+$/', $phone);
}

/**
 * 检查提交内容
 * @param  array      $data                需要检查的数据
 * @param  null/array $fields              需要检查的字段
 * @return bool       是否通过检查
 */
function check_fields($data, $fields = null)
{
    $data   = array_map('trim', $data);
    $fields = is_null($fields) ? array_keys($data) : $fields;

    foreach ($fields as $k) {
        if (!isset($data[$k]) || '' === trim($data[$k])) {
            return false;
        }
    }

    return true;
}

/**
 * [get_value description]
 * @param  [type]  $data           [description]
 * @param  [type]  $k              [description]
 * @param  string  $fallback       默认值
 * @param  boolean $empty          是否检查是否为空
 * @return [type]  [description]
 */
function get_value($data, $k, $fallback = '', $empty = false)
{
    return isset($data[$k]) ? ($empty ? (empty($data[$k]) ? $fallback : $data[$k]) : $data[$k]) : $fallback;
}

function get_pay_type($bank_name, $bank_list)
{
    $llpay     = $bank_list->llpay->toArray();
    $can_llpay = false;
    foreach ($llpay as $value) {
        # code...
        if (strpos($bank_name, $value['short_name']) !== false) {
            $can_llpay = true;
            break;
        }
    }

    $yeepay     = $bank_list->yeepay->toArray();
    $can_yeepay = false;
    foreach ($yeepay as $value) {
        # code...
        if (strpos($bank_name, $value['short_name']) !== false) {
            $can_yeepay = true;
            break;
        }
    }
    if ($can_llpay && $can_yeepay) {
        return 1;
    }
    if ($can_llpay) {
        return 2;
    }
    if ($can_yeepay) {
        return 3;
    }

    return 0;
}

// 生成一个长度$length为随机串
function create_str($length = 1)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
    $str   = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    return $str;
}

/**
 *  获取中文拼音
 *
 * @param  string   $str 字符串
 * @return string
 */
function chinese_to_pinyin($str)
{
    $str = trim($str);
    if (!$str) {
        return '';
    }

    mb_internal_encoding('utf8');
    $slen = mb_strlen($str);

    $pinyins = [];
    $fp      = fopen(FUNC_PATH . '/pinyin.dat', 'r');
    while (!feof($fp)) {
        $line = trim(fgets($fp));
        if (!$line) {
            continue;
        }

        $pinyins[mb_substr($line, 0, 1)] = mb_substr($line, 2);
    }
    fclose($fp);

    if (!$pinyins) {
        return $str;
    }

    $restr = [];
    for ($i = 0; $i < $slen; $i++) {
        $c = mb_substr($str, $i, 1);

        if (isset($pinyins[$c])) {
            $restr[] = ucfirst($pinyins[$c]);
        } else {
            $restr[] = $c;
        }
    }

    return implode(' ', $restr);
}

/**
 * 根据身份证获取生日，性别
 */
function info_from_id($id)
{
    $birthday = strlen($id) == 18 ? substr($id, 6, 8) : ('19' . substr($id, 6, 6));

    // 18位身份证倒数第二位奇数为男，偶数为女
    // 15为身份证最后一位奇数为男，偶数为女
    $gender = substr($id, (strlen($id) == 18 ? -2 : -1), 1) % 2 ? 'male' : 'female';

    return [
        'birthday' => $birthday,
        'gender'   => $gender,
    ];
}

function mixed_str_len($str, $chinese_len = 2)
{
    preg_match_all('/[\x{4E00}-\x{9FA5}\x{FE30}-\x{FFA0}\x{3000}-\x{3039}]/u', $str, $matches);

    $clen   = count($matches[0]);
    $strlen = utf8_len($str);

    return $strlen - $clen + $clen * $chinese_len;
}

/**
 * 截取固定长度的中英文混合的字符串
 */
function mixedStringSubstr($str, $start, $len)
{

    $tmpstr = '';
    $start  = $start ? $start : 0;
    $len    = $len ? $len : $maxLength;
    for ($i = $start; $i < strlen($str); $i++) {
        if (ord(mb_substr($str, $i, 1, 'utf-8')) > 128) {
            $len--;
        } else {
            $len -= 0.5;
        }

        if ($len < 0) {
            $str = mb_substr($str, $start, $i, 'utf-8');
        } elseif (0 == $len) {

            $str = mb_substr($str, $start, $i + 1, 'utf-8');
        }

    }

    return $str;
}

function get_current_ip()
{
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    } elseif (isset($_SERVER["REMOTE_ADDR"])) {
        $ip = $_SERVER["REMOTE_ADDR"];
    } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    } elseif (getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } elseif (getenv("REMOTE_ADDR")) {
        $ip = getenv("REMOTE_ADDR");
    } else {
        $ip = service('request')->getClientAddress();
    }

    return $ip;
}
/**
 * 返回格式化的 json 数据
 *
 * @param  array    $array
 * @param  boolean  $pretty    美化 json 数据
 * @param  boolean  $unescaped 关闭 Unicode 编码
 * @return string
 */
function json_it(array $array, $pretty = true, $unescaped = true)
{
    // php 5.4+
    if (defined('JSON_PRETTY_PRINT') && defined('JSON_UNESCAPED_UNICODE')) {
        if ($pretty && $unescaped) {
            $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
        } elseif ($pretty) {
            $options = JSON_PRETTY_PRINT;
        } elseif ($unescaped) {
            $options = JSON_UNESCAPED_UNICODE;
        } else {
            $options = null;
        }

        return json_encode($array, $options);
    }

    if ($unescaped) {
        // convmap since 0x80 char codes so it takes all multibyte codes (above ASCII 127).
        // So such characters are being "hidden" from normal json_encoding
        $tmp = [];
        array_walk_recursive($array, function (&$item, $key) {
            if (is_string($item)) {
                $item = mb_encode_numericentity($item, [0x80, 0xffff, 0, 0xffff], 'UTF-8');
            }
        });
        $json = mb_decode_numericentity(json_encode($array), [0x80, 0xffff, 0, 0xffff], 'UTF-8');
    } else {
        $json = json_encode($array);
    }

    if ($pretty) {
        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = "\t";
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i = 0; $i <= $strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string
            if ('"' == $char && '\\' != $prevChar) {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
                // output a new line and indent the next line.
            } elseif (('}' == $char || ']' == $char) && $outOfQuotes) {
                $result .= $newLine;
                $pos--;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if ((',' == $char || '{' == $char || '[' == $char) && $outOfQuotes) {
                $result .= $newLine;
                if ('{' == $char || '[' == $char) {
                    $pos++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        $json = $result;
    }

    return $json;
}

function sign($data, $key)
{
    logger(__FUNCTION__, var_export($data, true));
    $unset = [
        'key',
        '_url',
        'sign',
    ];
    foreach ($unset as $k) {
        if (isset($data[$k])) {
            unset($data[$k]);
        }
    }
    logger(__FUNCTION__, 'after' . var_export($data, true));
    $data['key'] = $key;
    logger(__FUNCTION__, http_build_query($data));
    return md5(http_build_query($data));
}

function sign_front($data, $key)
{
    $unset = [
        'key',
        '_url',
        'sign',
    ];
    foreach ($unset as $k) {
        if (isset($data[$k])) {
            unset($data[$k]);
        }
    }
    ksort($data);
    $query = http_build_query($data);
    return md5($query . $key);
}

function urlsafe_b64encode($string)
{
    $data = base64_encode($string);
    $data = str_replace(['+', '/', '='], ['-', '_', ''], $data);
    return $data;
}

function urlsafe_b64decode($string)
{
    $data = str_replace(['-', '_'], ['+', '/'], $string);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    return base64_decode($data);
}

function safe_input($value, $html_filter = true, $db_filter = true, $filter_key = false)
{
    if (is_array($value)) {
        if ($filter_key) {
            $temp = [];
            foreach ($value as $k => $v) {
                $temp_k        = safe_input($k, $html_filter, $db_filter);
                $temp[$temp_k] = safe_input($value[$k], $html_filter, $db_filter);
            }
            return $temp;
        } else {
            foreach ($value as $k => $v) {
                $value[$k] = safe_input($value[$k], $html_filter, $db_filter);
            }
        }
    } else {
        if ($html_filter) {
            $value = decode_html($value);
        }
        if ($db_filter) {
            $value = str_replace("'", "''", $value);
            $value = str_replace("\\", "\\\\", $value);
        }
    }
    return $value;
}

function decode_html($string)
{
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = decode_html($val);
        }
    } else {
        $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', str_replace(['&', '"', '<', '>'], ['&amp;', '&quot;', '&lt;', '&gt;'], $string));
    }
    return $string;
}

function removeXSS($val)
{
    // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
    // this prevents some character re-spacing such as <java\0script>
    // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
    $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

    // straight replacements, the user should never need these since they're normal characters
    // this prevents like <IMG SRC=@avascript:alert('XSS')>
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';
    $search .= '<>,';
    for ($i = 0; $i < strlen($search); $i++) {
        // ;? matches the ;, which is optional
        // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

                                                                                                       // @ @ search for the hex values
        $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val); // with a ;
                                                                                                       // @ @ 0{0,7} matches '0' zero to seven times
        $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val);              // with a ;
    }

    // now the only remaining whitespace attacks are \t, \n, and \r
    $ra1 = ['javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base'];
    $ra2 = ['onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload'];
    $ra  = array_merge($ra1, $ra2);

    $found = true; // keep replacing as long as the previous round replaced something
    while (true == $found) {
        $val_before = $val;
        for ($i = 0; $i < sizeof($ra); $i++) {
            $pattern = '/';
            for ($j = 0; $j < strlen($ra[$i]); $j++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                    $pattern .= '|';
                    $pattern .= '|(&#0{0,8}([9|10|13]);)';
                    $pattern .= ')*';
                }
                $pattern .= $ra[$i][$j];
            }
            $pattern .= '/i';
            $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
            $val         = preg_replace($pattern, $replacement, $val);         // filter out the hex tags
            if ($val_before == $val) {
                // no replacements were made, so exit the loop
                $found = false;
            }
        }
    }

    return $val;
}

function removeXSS2($string)
{
    $ra1 = ['javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base'];
    $ra2 = ['onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload'];

    foreach ($ra1 as $r) {
        $pattern = '/\<.*' . $r . '.*\>.*\<.*\/.*' . $r . '.*\>/i';
        $string  = preg_replace($pattern, '', $string);
    }

    foreach ($ra2 as $r) {
        $pattern = '/' . $r . '\s*=\s*".*"/i';
        $string  = preg_replace($pattern, '', $string);

        $pattern = '/' . $r . '\s*=\s*\'.*\'/i';
        $string  = preg_replace($pattern, '', $string);
    }

    $string = preg_replace('/[^\d\w\-\_\x{4E00}-\x{9FA5}\x{FE30}-\x{FFA0}\x{3000}-\x{3039} ]/u', '', $string);

    return trim($string);
}

function replace_storage_extension($url)
{
    if (strpos($url, "storage/") === false) {
        return $url;
    }
    return rev_extension($url);
}

//反转扩展名
function rev_extension($url)
{
    $url = preg_replace_callback('/\.([a-zA-Z]+)/', function ($match) {
        return "." . strrev($match[1]);
    }, $url);
    return $url;
}

function sendSms($sms_content, $phone)
{
    service('sms')->send($phone, $sms_content);
}


function round_pad_zero($num, $precision)
{
    if ($precision < 1) {
        return round($num, 0);
    }
    $r_num   = round($num, $precision);
    $num_arr = explode('.', "$r_num");
    if (count($num_arr) == 1) {
        return "$r_num" . '.' . str_repeat('0', $precision);
    }
    $point_str = "$num_arr[1]";
    if (strlen($point_str) < $precision) {
        $point_str = str_pad($point_str, $precision, '0');
    }
    return $num_arr[0] . '.' . $point_str;
}

/**
 * 将当前环境转换为字符串
 */
function env_str()
{
    switch (true) {
        case PRODUCTION:
            return 'production';
        case TESTING:
            return 'testing';
        default:
            return 'development';
    }
}
