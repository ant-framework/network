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

    protected $tail = '';

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

        $this->ivLength = openssl_cipher_iv_length($method);

        if (is_null($iv)) {
            $iv = openssl_random_pseudo_bytes($this->ivLength);
        }

        $this->method = $method;
        $this->key = $key;
        $this->iv = $iv;
        $this->options = $options;
        $this->isEncrypt = $isEncrypt;
        $this->handle = $isEncrypt
            ? 'openssl_encrypt'
            : 'openssl_decrypt';
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
    public function encrypt($data)
    {
        if (strlen($data) == 0) {
            return '';
        }

        $tailLength = strlen($this->tail);

        if ($tailLength) {
            $data = $this->tail . $data;
        }

        $byte = openssl_encrypt($data, $this->method, $this->key, $this->options, $this->iv);
        $result = substr($byte, $tailLength);

        $dataLength = strlen($data);
        $mod = $dataLength % $this->ivLength;

        if ($dataLength >= $this->ivLength) {
            $iPos = -($mod + $this->ivLength);
            $this->iv = substr($byte, $iPos, $this->ivLength);
        }

        $this->tail = $mod!=0 ? substr($data, -$mod) : '';

        return $result;
    }

    /**
     * @param $data
     * @return string
     */
    public function decrypt($data)
    {
        if (strlen($data) <= 0) {
            return '';
        }

        $tailLength = strlen($this->tail);

        if ($tailLength) {
            $data = $this->tail . $data;
        }

        $byte = openssl_decrypt($data, $this->method, $this->key, $this->options, $this->iv);
        $result = substr($byte, $tailLength);

        $dataLength = strlen($data);
        $mod = $dataLength % $this->ivLength;

        if ($dataLength >= $this->ivLength) {
            $iPos = -($mod + $this->ivLength);
            $this->iv = substr($data, $iPos, $this->ivLength);
        }

        $this->tail = $mod != 0 ? substr($data, -$mod) : '';

        return $result;
    }

    public function update($data)
    {
        return $this->isEncrypt
            ? $this->encrypt($data)
            : $this->decrypt($data);
//        if (strlen($data) <= 0) {
//            return '';
//        }
//
//        $tailLength = strlen($this->tail);
//
//        if ($tailLength) {
//            $data = $this->tail . $data;
//        }
//
//        $byte = call_user_func($this->handle, $data, $this->method, $this->key, $this->options, $this->iv);
//        $result = substr($byte, $tailLength);
//
//        $dataLength = strlen($data);
//        $mod = $dataLength % $this->ivLength;
//
//        if ($dataLength >= $this->ivLength) {
//            $iPos = -($mod + $this->ivLength);
//            $this->iv = substr($data, $iPos, $this->ivLength);
//        }
//
//        $this->tail = $mod != 0 ? substr($data, -$mod) : '';
//
//        return $result;
    }

//    public function update($data, $key, $iv, callable $handle, $tail = null)
//    {
//        $tailLength = strlen($tail);
//
//        if ($tailLength) {
//            $data = $tail . $data;
//        }
//
//        $byte = call_user_func_array($handle, [$data, $this->method, $key, $this->options, $this->iv]);
//        $result = substr($byte, $tailLength);
//
//        $dataLength = strlen($data);
//        $mod = $dataLength % $this->ivLength;
//
//        if ($dataLength >= $this->ivLength) {
//            $iPos = -($mod + $this->ivLength);
//            $iv = substr($data, $iPos, $this->ivLength);
//        }
//
//        $tail = $mod != 0 ? substr($data, -$mod) : '';
//
//        return [$result, $tail, $iv];
//    }
}