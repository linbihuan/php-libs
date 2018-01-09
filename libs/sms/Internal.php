<?php
/**
 * 功能: 国内短信发送
 *
* $sms = new \Sms\Internal();
* $sms->send('15923297663', 'test');
*/
namespace Sms;

class Internal
{
    const HTTP = 'http://api.sms.cn/mt/'; // 短信接口
    const UID  = 'linbihuan';                // 用户账号
    const PWD  = 'linbihuan';            // 密码

    protected static $codes = array(
        '100' => '发送成功',
        '101' => '验证失败',
        '102' => '短信不足',
        '103' => '操作失败',
        '104' => '非法字符',
        '105' => '内容过多',
        '106' => '号码过多',
        '107' => '频率过快',
        '108' => '号码内容空',
        '109' => '账号冻结',
        '110' => '禁止频繁单条发送',
        '112' => '号码不正确',
        '120' => '系统升级',
        '113' => '定时时间格式不对',
    );

    protected function _log($str)
    {
        logger('sms', '[@'.date('Y-m-d H:i:s').']'."#". $str);
    }

    protected function _post($url,$data='') // 以下post方法由官方提供
    {
        $row = parse_url($url);
        $host = $row['host'];
        $port = isset($row['port']) ? isset($row['port']):80;
        $file = $row['path'];
        $post = '';
        while (list($k,$v) = each($data)) {
            $post .= rawurlencode($k)."=".rawurlencode($v)."&"; // 转URL标准码
        }
        $post = substr( $post , 0 , -1 );
        $len = strlen($post);
        $fp = @fsockopen( $host ,$port, $errno, $errstr, 10);
        if (!$fp) {
            return "$errstr ($errno)\n";
        } else {
            $receive = '';
            $out = "POST $file HTTP/1.1\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Content-type: application/x-www-form-urlencoded\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Content-Length: $len\r\n\r\n";
            $out .= $post;
            fwrite($fp, $out);
            while (!feof($fp)) {
                $receive .= fgets($fp, 128);
            }
            fclose($fp);
            $receive = explode("\r\n\r\n",$receive);
            unset($receive[0]);

            return implode("",$receive);
        }
    }

    // ====================
    // 外部接口
    // ====================

    /**
     * 发送短信
     * @param $mobile  string
     * @param $msg     string
     * @param $exinfo string
     * @return array
     */
    public function send($mobile,$msg)
    {
        if (empty($mobile) || empty($msg)) return false;

        $id   = uniqid();
        $data = array(
            'uid'       => self::UID,                  // 用户账号
            'pwd'       => md5(self::PWD . self::UID), // MD5位32密码,密码和用户名拼接字符
            'mobile'    => $mobile,                    // 号码
            'content'   => $msg,                       // 内容
            'mobileids' => '',
            'encode'    => 'utf8',
        );
        $this->_log("{$id} #req-> mobile: [{$mobile}], msg: [{$msg}]");

        $res  = trim(to_utf8($this->_post(self::HTTP, $data))); // POST方式提交
        $code = preg_replace('/^.*stat=(\d+).*$/', '$1', $res);

        if (empty($res)) {
            $this->_log("{$id} #res-> Api Exception on branch1");

            return array('res' => 100, 'msg' => '网络繁忙，请稍后再试');
        }

        if ( ! isset(self::$codes[$code])) {
            $this->_log("{$id} #res-> Api Exception on branch2 -> res: {$res}");

            return array('res' => 200, 'msg' => '网络繁忙，请稍后再试');
        }

        switch ($code) {
            case '100':
                $status = 0;
                $msg = 'OK';
                break;
            case '107':
            case '110':
                $status = 300;
                $msg = '请1分钟后再试';
                break;
            default:
                $status = 400;
                $msg = '服务器异常，发送短信失败，请稍后再试';
                break;
        }

        $this->_log("{$id} #res-> status: {$status}, code: {$code}, res: ".$res);

        return array('res' => $status, 'msg' => $msg);
    }
}
