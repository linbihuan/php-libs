<?php

namespace Base;

/**
 * Cookie 处理
 */
class Cookies
{

    /**
     * 默认生存周期 7 天，单位：秒
     *
     * @var integer
     */
    protected $_lifetime = 604800;

    /**
     * Cookie 存储路径
     *
     * @var string
     */
    protected $_path = '/';

    /**
     * Cookie 域名范围
     *
     * @var string
     */
    protected $_domain = null;

    /**
     * 是否启用 https 连接传输
     *
     * @var boolean
     */
    protected $_secure = false;

    /**
     * 仅允许 http 访问，禁止 javascript 访问
     *
     * @var boolean
     */
    protected $_httponly = false;

    /**
     * 是否启用 crypt 加密
     *
     * @var boolean
     */
    protected $_encrypt = true;

    /**
     * 构造方法
     *
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        if ($options !== null) {
            $this->setOptions($options);
        }
    }

    /**
     * 设置 Cookie 选项
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $ref = new \ReflectionObject($this);

        foreach ($options as $key => $value) {
            if ($ref->hasProperty("_$key")) {
                $this->{"_$key"} = $value;
            }
        }
    }

    /**
     * 设置 cookie 值
     *
     * @param string  $name
     * @param mixed   $value
     * @param integer $lifetime
     */
    public function set($name, $value, $lifetime = null)
    {
        if ($lifetime === null) {
            $lifetime = $this->_lifetime;
        }

        // 加密
        if ($this->_encrypt) {
            $value = service('crypt')->encrypt($value);
        }

        // 设置 cookie
        setcookie($name, $value, time() + $lifetime, $this->_path, $this->_domain, $this->_secure, $this->_httponly);
    }

    /**
     * 获取 cookie 值
     *
     * @param  array $name
     * @param  mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (! isset($_COOKIE[$name])) {
            return $default;
        }

        $data = $_COOKIE[$name];

        // 解密
        if ($this->_encrypt) {
            $data = service('crypt')->decrypt($data);
        }

        return $data;
    }

    /**
     * 判断 cookie 是否存在
     *
     * @param  string  $name
     * @return boolean
     */
    public function has($name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * 删除 cookie
     *
     * @param  string  $name
     * @return boolean
     */
    public function delete($name)
    {
        // Remove the cookie
        unset($_COOKIE[$name]);

        // Nullify the cookie and make it expire
        setcookie($name, null, -86400, $this->_path, $this->_domain, $this->_secure, $this->_httponly);

        return true;
    }

    /**
     * 清空所有 cookie
     */
    public function reset()
    {
        $_COOKIE = null;
    }

    /**
     * 返回所有 cookie
     */
    public function toArray()
    {
        return $_COOKIE;
    }

}
