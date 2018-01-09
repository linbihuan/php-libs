<?php

/**
 * 定义url开头的一些url实用方法
 */

/**
 * 获取完整的 url 地址
 *
 * @see    http://docs.phalconphp.com/zh/latest/api/Phalcon_Mvc_Url.html
 *
 * @param  string   $uri
 * @return string
 */
function url($uri = null)
{
    // 网址链接及非正常的 url，纯锚点 (#...) 和 (javascript:)
    if (preg_match('~^(#|javascript:|https?://|telnet://|ftp://|tencent://)~', $uri)) {
        return $uri;
    }

    return service('url')->get(ltrim($uri, '/'));
}

/**
 * 获取静态资源地址
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Url.html
 *
 * @param  string   $uri
 * @param  string   $time
 * @return string
 */
function url_static($uri = null, $time = true)
{
    $params = $time && !preg_match('~(&|\?)t=\d+$~i', $uri)
    ? ['t' => PRODUCTION ? app_update_time() : time()]
    : null;

    return preg_match('~^https?://~i', $uri)
    ? url_param($uri, $params)
    : url_param(service('url')->getStatic(ltrim($uri, '/')), $params);
}

/**
 * 获取 JS 网址
 *
 * @param  string   $jsfile
 * @param  boolean  $jsfile
 * @return string
 */
function url_js($jsfile = null, $time = false)
{
    $jsfile = ltrim($jsfile, '/');

    if (empty($jsfile)) {
        $time = false;
    }

    $dir = PRODUCTION ? 'js' : 'js_src';
    $dir = _g('freshjs') === '1' ? 'js_src' : $dir;

    return url_static($dir . '/' . $jsfile, $time);
}

/**
 * 获取包含域名在内的 url
 *
 * @param  string   $uri
 * @param  string   $base
 * @return string
 */
function url_base($uri = null, $base = HTTP_BASE)
{
    return HTTP_BASE . ltrim($uri, '/');
}

/**
 * 根据 query string 参数生成 url
 *
 *     url_param('item/list', array('page' => 1)) // item/list?page=1
 *     url_param('item/list?page=1', array('limit' => 10)) // item/list?page=1&limit=10
 *
 * @param  string   $uri
 * @param  array    $params
 * @return string
 */
function url_param($uri, array $params = null, $is_escape = true)
{
    if (null === $uri) {
        $uri = HTTP_URL;
    }

    if (empty($params)) {
        return $uri;
    }

    $parts   = parse_url($uri);
    $queries = [];
    if (isset($parts['query']) && $parts['query']) {
        parse_str($parts['query'], $queries);
    }

    // xss 修正
    $params    = array_merge($queries, $params);
    $newParams = [];
    foreach ($params as $key => $val) {
        $key             = htmlspecialchars($key, ENT_QUOTES);
        $val             = htmlspecialchars($val, ENT_QUOTES);
        $newParams[$key] = $val;
    }

    // 重置 query 组件
    $parts['query'] = rawurldecode(http_build_query($newParams, null, ($is_escape) ? '&amp;' : '&'));

    return http_build_url($uri, $parts);
}

/**
 * 使用 sprintf 对 url 进行格式化
 *
 *      url_format('item/%s-%d.html', 'books', 1) // /item/books-1.html
 *
 * @return string
 */
function url_format()
{
    return url(call_user_func_array('sprintf', func_get_args()));
}

/**
 *  调用百度短链接api
 * @param  [string]  $url                      [要处理的长链接]
 * @param  [boolean] $noHttp                   [去掉'http://']
 * @return [string]  [生成后的短链接]
 */
function _shortUrlFromBaidu($url, $noHttp = true)
{

    $ch = curl_init();
    //用别人的appid
    $api_url = sprintf('http://api.t.sina.com.cn/short_url/shorten.json?source=%s&url_long=%s', '3271760578', urlencode($url));
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $strRes = curl_exec($ch);
    curl_close($ch);
    $arrResponse = json_decode($strRes, true);
    logger(__FUNCTION__, var_export($arrResponse, true));
    if (!isset($arrResponse['error_code']) && count($arrResponse) > 0) {
        return $noHttp ? str_replace("http://", "", $arrResponse[0]['url_short']) : $arrResponse[0]['url_short'];
    }
    return $url;
}

/**
 * 获取或指定当前的带有语言选项的request_uri
 * @param  $lang
 * @return string
 */
function lang_uri($lang = '')
{
    $lang_uri = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES);
    $lang_uri = preg_replace('/([&\?])lang=[-a-z]{2,5}$/', '', $lang_uri); // 去除 lang=xx
    $lang_uri .= strpos($lang_uri, '?') === false ? '?lang=' : '&lang=';
    $lang_uri .= $lang;

    return $lang_uri;
}

/**
 * 验证http refer 防csrf
 * @param  [type] $check_url      [description]
 * @return [type] [description]
 */
function check_http_refer($check_url)
{
    $refer_url_info = parse_url($_SERVER['HTTP_REFERER']);
    $check_url      = !is_array($check_url) ? [$check_url] : $check_url;
    foreach ($check_url as $v) {
        $check_url_info = parse_url($v);
        if (strcasecmp($refer_url_info['host'], $check_url_info['host']) == 0 && strcasecmp($refer_url_info['path'], $check_url_info['path']) == 0) {
            return true;
        }
    }

    return false;
}

// 对url 的参数进行 urlencode的 操作
function urlencodeParams($url)
{
    preg_match('/^(.*?)\?(.*)/', $url, $matchs);

    if (count($matchs) <= 2) {
        return $url;
    }

    $urlHead   = $matchs[1];
    $urlParams = $matchs[2];

    $params = explode('&', $urlParams);

    $num = count($params);
    for ($i = 0; $i < $num; $i++) {
        $temp = $params[$i];

        $temps      = explode('=', $temp);
        $temps[1]   = urlencode($temps[1]);
        $params[$i] = $temps[0] . '=' . $temps[1];
    }

    return $urlHead . '?' . implode('&', $params);
}
