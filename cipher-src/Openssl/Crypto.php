<?php
namespace Ant\Crypt\Openssl;

use RuntimeException;
use InvalidArgumentException;
use Ant\Crypt\CipherInterface;

/**
 * todo 支持Aead加密
 *
 * Class Openssl
 * @package Ant\Ciphers
 */
class Crypto implements CipherInterface
{
    protected $method;

    protected $key;

    protected $iv;

    protected $options;

    protected $fmt;

    /**
     * Openssl constructor.
     * @param $method
     * @param $key
     * @param $iv
     * @param $options
     */
    public function __construct(
        $method,
        $key,
        $iv = null,
        $options = OPENSSL_RAW_DATA
    ) {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException("请安装openssl扩展");
        }

        $ciphers = openssl_get_cipher_methods();

        if (!in_array($method, $ciphers)) {
            throw new InvalidArgumentException("Invalid cipher name [{$method}]");
        }

        if (is_null($iv)) {
            $ivLen = openssl_cipher_iv_length($method);
            $iv = openssl_random_pseudo_bytes($ivLen);
        }

        $this->method = $method;
        $this->key = $key;
        $this->iv = $iv;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getIv()
    {
        return $this->iv;
    }

    /**
     * @return int
     */
    public function getIvLength()
    {
        return strlen($this->iv);
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return int
     */
    public function getKeyLength()
    {
        return strlen($this->key);
    }

    /**
     * @param $data
     * @param null $key
     * @param null $iv
     * @return string
     */
    public function encrypt($data, $key = null, $iv = null)
    {
        if (is_null($key)) {
            $key = $this->key;
        }

        if (is_null($iv)) {
            $iv = $this->iv;
        }

        return openssl_encrypt($data, $this->method, $key, $this->options, $iv);
    }

    /**
     * @param $data
     * @param null $key
     * @param null $iv
     * @return string
     */
    public function decrypt($data, $key = null, $iv = null)
    {
        if (is_null($key)) {
            $key = $this->key;
        }

        if (is_null($iv)) {
            $iv = $this->iv;
        }

        return openssl_decrypt($data, $this->method, $key, $this->options, $iv);
    }
}