<?php

namespace Mailer;

/**
 * 邮件服务
 *
 * 提供邮件发送服务
 *
 */
class Sendcloud
{
    const SEND_FROM     = 'linbihuan@gmail.com';
    const SEND_FROMNAME = 'linbihuan';

    protected static $instance = NULL;

    protected $_params      = array();
    protected $_contentType = 'text/html';
    protected $_timeout     = 30; // seconds

    public function __construct()
    {
        if (self::$instance === NULL) {
            self::$instance = $this;
        }

        return self::$instance;
    }

    // 单例模式防止 __clone
    private function __clone() {}

    // Make sure to add only valid email addresses as recipients.
    // If you try to add an invalid email address with setTo(), setCc() or setBcc(),
    // Swift Mailer will throw a Swift_RfcComplianceException.
    protected function filterEmails($emails)
    {
        $emails = is_array($emails) ? $emails : explode(';', $emails);
        $res = array();
        foreach($emails as $k => $v) {
            if ( ! is_email($k) && ! is_email($v)) continue;
            $res[$k] = $v;
        }
        return $res;
    }

    protected function post($url, array $data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 自cURL 7.10开始默认为 TRUE
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, $url);

        // 附件处理
        if (isset($data['files']) && is_array($data['files'])) {
            // The usage of the @filename API for file uploading is deprecated.
            // Please use the CURLFile class instead 错误兼容处理
            if (class_exists('\CURLFile')) {
                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
            } elseif (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
            }

            $i     = 0;
            $files = $data['files'];
            unset($data['files']);

            foreach ($files as $file) {
                $file = realpath($file);
                if ( ! is_file($file)) continue;

                $data['file'.(++$i)] = class_exists('\CURLFile')
                    ? (new \CURLFile($file))
                    : ('@' . $file);
            }
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * 执行 API 请求
     *
     * @param  arrray $data
     * @return mixed
     */
    protected function request(array $data)
    {
        $config = config('email.sendcloud');
        $api    = $config->gateway;

        $data['api_user'] = $config->apiuser;
        $data['api_key']  = $config->apikey;

        // from
        $data['fromname'] = $config->from->name;
        $data['from']     = $config->from->email;
        if (isset($this->_params['from']) && is_email($this->_params['from'])) {
            $data['from'] = $this->_params['from'];
        }
        if (isset($this->_params['fromname']) && $this->_params['fromname']) {
            $data['fromname'] = $this->_params['fromname'];
        }

        // 抄送
        if (isset($this->_params['cc'])) {
            $data['cc'] = implode(';', $this->filterEmails($this->_params['cc']));
        }

        // 暗送
        if (isset($this->_params['bcc'])) {
            $data['bcc'] = implode(';', $this->filterEmails($this->_params['bcc']));
        }

        // 回复
        if (isset($this->_params['replyto']) && is_email($this->_params['replyto'])) {
            $data['replyto'] = $this->_params['replyto'];
        }

        // 添加附件
        if (isset($this->_params['files'])) {
            $data['files'] = (array) $this->_params['files'];
        }

        return $this->post($api, $data);
    }

    public function setParams(array $array = array())
    {
        $this->_params = array_merge($this->_params, $array);

        return $this;
    }

    public function setTimeout($timeout)
    {
        $this->_timeout = (int) $timeout;

        return $this;
    }

    public function getParams()
    {
        return $this->_params;
    }

    public function setContentType($contentType = 'text/html')
    {
        $this->_contentType = $contentType;

        return $this;
    }

    /**
     * 发送邮件
     *
     * @see   http://sendcloud.sohu.com/sendcloud/api-doc/web-api-mail-detail
     * @param  string  $email   收信人的Email
     * @param  string  $subject 主题
     * @param  string  $body    正文
     * @return boolean
     */
    public function send($to, $subject, $body)
    {
        $to = $this->filterEmails($to);
        if (empty($to) || empty($subject) || empty($body)) {
            return 'Invalid argument';
        }
        $to = implode(';', $to);

        // 不同于登录SendCloud站点的帐号，您需要登录后台创建发信域名，获得对应发信域名下的帐号和密码才可以进行邮件的发送。
        return $this->request(array(
            'to'      => $to, // 多个email用分号隔开, 如: em@g.com;ab@g.com
            'subject' => $subject,
            'html'    => $body, // 支持html
        ));
    }

}
