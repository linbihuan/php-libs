<?php

namespace AES\Encrypt;

/**
 * AES 算法加密解密类
 */
class AES
{

    protected static $instance = null;
    public $_iv                = '1234566';
    public $_secret            = 'abcdefg';
    public $_mode              = 'CRYPT_AES_MODE_CBC';
    public $cipher;

    public function __construct()
    {
        if (null === self::$instance) {
            self::$instance = $this;
        }

        return self::$instance;
    }

    // 单例模式防止 __clone
    private function __clone()
    {}

    public static function instance()
    {
        if (null !== self::$instance) {
            return self::$instance;
        }

        return new self;
    }

    /**
     * 设置参数
     * @param [type] $param [description]
     */
    public function setParam($param)
    {
        if (isset($param['mode'])) {
            $this->setMode($param['mode']);
        }
        if (isset($param['iv'])) {
            $this->setIV($param['iv']);
        }
        if (isset($param['key'])) {
            $this->setSecret($param['key']);
        }
    }

    /**
     * Crypt_AES instance
     *
     * @param  string       $secret
     * @return \Crypt_AES
     */
    public function setMode($mode)
    {
        $this->_mode  = $mode;
        $this->cipher = new \Crypt_AES($this->_mode);
        return $this;
    }

    /**
     * 设置IV
     * @param string $cipher_iv
     */
    public function setIV($iv)
    {
        $this->_iv = $iv;
        $this->cipher->setIv($this->_iv);
        return $this;
    }

    /**
     * 设置key
     * @param string $secret
     */
    public function setSecret($secret, $method = '')
    {
        $this->_secret = ($method) ? $method('sha256', $secret, true) : $secret;
        $this->cipher->setKey($this->_secret);
        return $this;
    }

    /**
     * Encrypts a plain text
     *
     * @param  string   $text
     * @param  string   $secret
     * @return string
     */
    public function encrypt($text, $origin = false)
    {
        return $origin
        ? ($this->cipher->encrypt($text))
        : base64_encode($this->cipher->encrypt($text));
    }

    /**
     * Decrypts a cipher text
     *
     * @param  string   $text
     * @param  string   $secret
     * @return string
     */
    public function decrypt($text, $origin = false)
    {
        return $origin
        ? $this->cipher->decrypt($text)
        : $this->cipher->decrypt(base64_decode($text));
    }

    /**
     * 随机生成16位字符串
     * @return string 生成的字符串
     */
    public function getRandomStr($num = 8)
    {
        $str    = "";
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max    = strlen($strPol) - 1;
        for ($i = 0; $i < $num; $i++) {
            $str .= $strPol[mt_rand(0, $max)];
        }
        return $str;
    }

}
