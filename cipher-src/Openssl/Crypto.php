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

    protected $options;

    protected $ivLength;
    // todo 解决iv公用的问题
    protected $iv;

    protected $encryptTail = '';

    protected $decryptTail = '';

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

        $this->ivLength = openssl_cipher_iv_length($method);

        if (is_null($iv)) {
            $iv = openssl_random_pseudo_bytes($this->ivLength);
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
     * @param null $key
     * @param null $iv
     * @return string
     */
    public function encrypt($data, $key = null, $iv = null)
    {
        if (strlen($data) == 0)
            return '';
        $tl = strlen($this->encryptTail);
        if ($tl)
            $data = $this->encryptTail . $data;
        $b = openssl_encrypt($data, $this->method, $this->key, OPENSSL_RAW_DATA, $this->iv);
        $result = substr($b, $tl);
        $dataLength = strlen($data);
        $mod = $dataLength % $this->ivLength;
        if ($dataLength >= $this->ivLength) {
            $iPos = -($mod + $this->ivLength);
            $this->iv = substr($b, $iPos, $this->ivLength);
        }
        $this->encryptTail = $mod!=0 ? substr($data, -$mod):'';
        return $result;

//        if (strlen($data) <= 0) {
//            return '';
//        }
//
//        if (is_null($key)) {
//            $key = $this->key;
//        }
//
//        if (is_null($iv)) {
//            $iv = $this->iv;
//        }
//
//        list($result, $this->encryptTail, $this->iv) = $this->update($data, $key, $iv, 'openssl_encrypt', $this->encryptTail);
//
//        return $result;
    }

    /**
     * @param $data
     * @param null $key
     * @param null $iv
     * @return string
     */
    public function decrypt($data, $key = null, $iv = null)
    {
        if (strlen($data) == 0)
            return '';
        $tl = strlen($this->decryptTail);
        if ($tl)
            $data = $this->decryptTail . $data;
        $b = openssl_decrypt($data, $this->method, $this->key, OPENSSL_RAW_DATA, $this->iv);
        $result = substr($b, $tl);
        $dataLength = strlen($data);
        $mod = $dataLength%$this->ivLength;
        if ($dataLength >= $this->ivLength) {
            $iPos = -($mod + $this->ivLength);
            $this->iv = substr($data, $iPos, $this->ivLength);
        }
        $this->decryptTail = $mod!=0 ? substr($data, -$mod):'';
        return $result;
//        if (strlen($data) <= 0) {
//            return '';
//        }
//
//        if (is_null($key)) {
//            $key = $this->key;
//        }
//
//        if (is_null($iv)) {
//            $iv = $this->iv;
//        }
//
//        list($result, $this->decryptTail, $this->iv) = $this->update($data, $key, $iv, 'openssl_decrypt', $this->decryptTail);
//
//        return $result;
    }

    protected function update($data, $key, $iv, callable $handle, $tail = null)
    {
        $ivLength = $this->getIvLength();
        $tailLength = strlen($tail);

        if ($tail) {
            $data = $tail . $data;
        }

        $result = call_user_func_array($handle, [$data, $this->method, $key, $this->options, $iv]);
        $result = substr($result, $tailLength);
        $dataLength = strlen($data);
        $mod = $dataLength % $ivLength;

        if ($dataLength >= $ivLength) {
            $iPos = -($mod + $ivLength);
            $iv = substr($data, $iPos, $ivLength);
        }

        $tail = $mod != 0 ? substr($data, -$mod) : '';

        return [$result, $tail, $iv];
    }
}