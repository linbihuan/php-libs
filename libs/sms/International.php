<?php
/**
 * 功能: 国际短信发送
 * $sms = new \Sms\International();
 * $sms->code('86')->send('15923297663', 'test');
 */
namespace Sms;

class International
{
    const API = 'http://m.5c.com.cn/api/send/?';    // 短信接口
    const USR = 'linbihuan';                         // 用户账号
    const PSD = 'linbihuan';                      // 用户账号
    const KEY = 'sldflasdnlbnlwejofladsf'; // API KEY

    private $country_code = '';

    protected function _log($str)
    {
        logger('sms', '[@'.date('Y-m-d H:i:s').']'."#". $str);
    }

    protected function _post($url,$post_fields='') // 以下post方法由官方提供
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600); // 60秒
        curl_setopt($ch, CURLOPT_HEADER,1);
        curl_setopt($ch, CURLOPT_REFERER,'http://linbihuan.com');
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$post_fields);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = explode("\r\n\r\n",$data);

        return $res[2];
    }

    // ====================
    // 外部接口
    // ====================

    // 指定国际区号
    public function code($code)
    {
        $this->country_code = $code;

        return $this;
    }

    /**
     * 发送短信, 有些国家的短信不自动拆分，请注意短信的长度
     *      美国、墨西哥、韩国不支持短信合并。如有长短信需求,请自行拆分。
     *      ASCII 普通短信 160 字,Unicode 普通短信支持 70 字。
     *      ASCII 长短信按 154 字拆分,Unicode 长短信按 65 字拆分。
     *
     * @param $mobile  string
     * @param $msg     string
     * @return array
     */
    public function send($mobile,$msg)
    {
        if (empty($mobile) || empty($msg)) return false;

        // 追加国际区号
        // +81 09012345678
        // +(81)09012345678
        // 8109012345678
        // 统统转换为：8109012345678
        $number = str_replace(array(' ','+','-',')','('), '', $this->country_code . $mobile);

        $id   = uniqid();
        $data = array(
            'username' => self::USR, // 用户账号
            'password' => self::PSD, // 密码
            'apikey'   => self::KEY, // apikey

            'mobile'   => $number,      // 号码
            'content'  => to_gbk($msg), // 内容
        );
        $this->_log("{$id} #req-> country_code: [{$this->country_code}], mobile: [{$mobile}], msg: [{$msg}]");

        $res = trim($this->_post(self::API, $data)); // POST方式提交

        if (empty($res)) {
            $this->_log("{$id} #res-> Api Exception");

            return array('res' => 100, 'msg' => '服务器异常，发送短信失败，请稍后再试');
        }

        // success:msgid
        // error:msgid
        // error:Missing username
        // error:Missing password
        // error:Missing apikey
        // error:Missing recipient
        // error:Missing message content
        // error:Account is blocked
        // error:Unrecognized encoding
        // error:APIKEY or password error
        // error:Unauthorized IP address
        // error:Account balance is insufficient
        // error:Black keywords is:党中央
        switch (true) {
            case (stripos($res, 'success:') === 0):
                $status = 0;
                $msg    = 'OK';
                break;

            default:
                $status = 200;
                $msg    = '网络繁忙，请稍后再试';
                break;
        }

        $this->_log("{$id} #res-> status: {$status}, res: ".$res);

        return array('res' => $status, 'msg' => $msg);
    }
}
