<?php

namespace Mailer;

/**
 * 发email相关
 */
class Swift
{

    protected $_params      = array();
    protected $_contentType = 'text/html';
    protected $_timeout     = 30; // seconds
    protected $_conf        = 'smtp'; // seconds

    protected static $instance = NULL;

    /**
     * 构造方法
     */
    public function __construct($conf='')
    {
        if($conf) $this->_conf = $conf;

        if ( ! class_exists('Swift_Mailer')) {
            throw new Exception('Require components: Swift_Mailer (ref: http://swiftmailer.org/)');
        }

        if (self::$instance === NULL) {
            self::$instance = $this;
        }

        return self::$instance;
    }

    // 单例模式防止 __clone
    private function __clone() {}

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

    // Make sure to add only valid email addresses as recipients.
    // If you try to add an invalid email address with setTo(), setCc() or setBcc(),
    // Swift Mailer will throw a Swift_RfcComplianceException.
    public function filterEmails($emails)
    {
        $emails = is_array($emails) ? $emails : explode(';', $emails);
        $res = array();
        foreach($emails as $k => $v) {
            if ( ! is_email($k) && ! is_email($v)) continue;
            $res[$k] = $v;
        }
        return $res;
    }

    /**
     * 发送邮件
     *
     * @param  string|array $to
     * @param  string       $subject
     * @param  string       $body
     * @return mixed
     */
    public function send($to, $subject, $body)
    {
        $to = $this->filterEmails($to);
        if (empty($to) || empty($subject) || empty($body)) {
            return 'Invalid argument';
        }

        $config = config('email.' . $this->_conf);

        // 创建 transport
        $transport = \Swift_SmtpTransport::newInstance($config->host, $config->port)
            ->setTimeout($this->_timeout)
            ->setUsername($config->username)
            ->setPassword($config->password);

        // 创建邮件消息
        $message = \Swift_Message::newInstance($subject, $body, $this->_contentType)
            ->setCharset('UTF-8');

        // 发件人
        $message->setFrom(
            isset($this->_params['from']) ? $this->_params['from'] : $config->from->email,
            isset($this->_params['fromname']) ? $this->_params['fromname'] : $config->from->name
        );

        // 收件人
        $message->setTo($to);

        // 抄送
        if (isset($this->_params['cc'])) {
            $message->setCc($this->filterEmails($this->_params['cc']));
        }

        // 暗送
        if (isset($this->_params['bcc'])) {
            $message->setBcc($this->filterEmails($this->_params['bcc']));
        }

        // 回复
        if (isset($this->_params['replyto']) && is_email($this->_params['replyto'])) {
            $message->setReplyTo($this->_params['replyto']);
        }

        // 添加附件
        if (isset($this->_params['files'])) {
            $files = (array) $this->_params['files'];
            foreach ($files as $file) {
                if ( ! is_file($file)) continue;
                $message->attach(\Swift_Attachment::fromPath($file));
            }
        }

        // 发送邮件
        $mailer = \Swift_Mailer::newInstance($transport);

        // To use the ArrayLogger
        $logger = new \Swift_Plugins_Loggers_ArrayLogger();
        $mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($logger));

        try{
            $res = $mailer->send($message);
            if ($res) return 'success';

            $err = $logger->dump();
            $err = trim(preg_replace('/^.+<<(.+?)!! .+$/s', '$1', $err));
            $res = 'failure:'.$err;

        } catch(\Exception $e) {
            $res = 'failure:'.$e->getMessage();
        }

        return $res;
    }

}
