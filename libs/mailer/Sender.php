<?php

namespace Mailer;

/**
 * 发email相关
 */
class Sender
{

    protected $_channel         = null;
    protected $_mailer          = null;

    /**
     * 构造方法
     */
    public function __construct($channel = null)
    {
        $this->_channel = $channel;

        if ($channel === 'swift') {
            return $this->_mailer = new \Mailer\Swift;
        }

        if ($channel === 'sendcloud') {
            return $this->_mailer = new \Mailer\Sendcloud;
        }

        if ($channel === 'swift-settlement') {
            return $this->_mailer = new \Mailer\Swift('swift-settlement');
        }

        // 默认50%的概率使用swift发送邮件，50%的概率使用sendcloud发邮件
        if (mt_rand(1,100) <= 50) {
            $this->_channel = 'swift';
            return $this->_mailer = new \Mailer\Swift;
        }

        $this->_channel = 'sendcloud';
        return $this->_mailer = new \Mailer\Sendcloud;
    }

    public static function instance($channel = null)
    {
        return new self($channel);
    }

    public function setParams(array $array = array())
    {
        $this->_mailer->setParams($array);

        return $this;
    }

    public function getParams()
    {
        return $this->_mailer->getParams();
    }

    public function setTimeout($timeout)
    {
        $this->_mailer->setTimeout($timeout);

        return $this;
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
        $t1 = microtime(true);
        $res   = $this->_mailer->send($to, $subject, $body);
        $t2 = microtime(true);
        $piece = array(
            'email'   => $to,
            'channel' => $this->_channel,
            'subject' => $subject,
            'body'    => $body,
            'params'  => $this->getParams(),
            'result'  => $res,
        );

        logger(
            'sendEmail',
            '$_'.uniqid().' = '.var_export($piece, true).';'
            ."\n".'spend time:'.($t2-$t1)
        );
        \EmailList::add($piece);

        return $res;
    }

    /**
     * 简单的发送邮件，不写入队列表，不写入日志
     *
     * @param  string|array $to
     * @param  string       $subject
     * @param  string       $body
     * @return mixed
     */
    public function simplySend($to, $subject, $body)
    {
        return $this->_mailer->send($to, $subject, $body);
    }

}
