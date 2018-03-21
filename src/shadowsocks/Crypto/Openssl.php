<?php
namespace Ant\Network\Shadowsocks\Crypto;

use RuntimeException;
use InvalidArgumentException;

/**
 * todo 支持Aead加密
 *
 * Class Openssl
 * @package Ant\Network\Shadowsocks\Crypto
 */
class Openssl
{
    protected $method;

    protected $key;

    protected $isEncrypt;

    protected $handle;

    protected $options;

    protected $ivLength;

    protected $iv;

    protected $block = '';

    /**
     * Openssl constructor.
     * @param $method
     * @param $key
     * @param $isEncrypt
     * @param $iv
     * @param $options
     */
    public function __construct(
        $method,
        $key,
        $isEncrypt,
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

        $this->method = $method;
        $this->key = $key;
        $this->ivLength = openssl_cipher_iv_length($method);
        $this->options = $options;
        $this->isEncrypt = $isEncrypt;
        $this->handle = $isEncrypt
            ? 'openssl_encrypt'
            : 'openssl_decrypt';

        if (is_null($iv)) {
            $this->iv = openssl_random_pseudo_bytes($this->ivLength);
        } else {
            $this->iv = substr($iv, 0, $this->ivLength);
        }
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
        return $this->ivLength;
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
     * @return string
     */
    public function update($data)
    {
        if (empty($data)) {
            return '';
        }

        $blockSize = strlen($this->block);

        if ($blockSize) {
            $data = $this->block . $data;
        }

        $buffer = call_user_func($this->handle, $data, $this->method, $this->key, $this->options, $this->iv);
        $result = substr($buffer, $blockSize);

        $dataLength = strlen($data);
        $mod = $dataLength % $this->ivLength;

        if ($dataLength >= $this->ivLength) {
            $iPos = -($mod + $this->ivLength);
            // 生成下一段加密用的iv
            $this->iv = substr($this->isEncrypt ? $buffer : $data, $iPos, $this->ivLength);
        }

        $this->block = $mod != 0 ? substr($data, -$mod) : '';

        return $result;
    }
}