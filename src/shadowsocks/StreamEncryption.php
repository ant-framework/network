<?php
namespace Ant\Network\Shadowsocks;

use InvalidArgumentException;
use Ant\Network\Shadowsocks\Crypto\Openssl;

/**
 * todo 更多的加密方式
 *
 * Class StreamEncryption
 * @package Ant\Network\Shadowsocks
 */
class StreamEncryption
{
    const METHOD_INFO_KEY_LEN = 0;
    const METHOD_INFO_IV_LEN = 1;
    const METHOD_INFO_CRYPTO = 2;

    /**
     * 预共享秘钥
     *
     * @var string
     */
    protected $password;

    /**
     * 混淆秘钥
     *
     * @var string
     */
    protected $key;

    /**
     * iv是否发送
     *
     * @var boolean
     */
    protected $ivSent = false;

    /**
     * @var array
     */
    protected $cachedKeys = [];

    /**
     * 加密方式
     *
     * @var string
     */
    protected $method;

    /**
     * @var
     */
    protected $encipher;

    /**
     * @var
     */
    protected $decipher;

    /**
     * @var array
     */
    protected $methods = [
        // openssl 加密
        'aes-128-ctr'       =>  [16, 16, Openssl::class],
        'aes-192-ctr'       =>  [24, 16, Openssl::class],
        'aes-256-ctr'       =>  [32, 16, Openssl::class],
        'aes-128-cfb'       =>  [16, 16, Openssl::class],
        'aes-192-cfb'       =>  [24, 16, Openssl::class],
        'aes-256-cfb'       =>  [32, 16, Openssl::class],
        'camellia-128-cfb'  =>  [16, 16, Openssl::class],
        'camellia-192-cfb'  =>  [24, 16, Openssl::class],
        'camellia-256-cfb'  =>  [32, 16, Openssl::class],
        'chacha20-ietf'     =>  [32, 12, Openssl::class],
    ];

    public function __construct($password, $method = 'aes-128-cfb')
    {
        if (!array_key_exists($method, $this->methods)) {
            throw new InvalidArgumentException;
        }

        $this->password = $password;
        $this->method = strtolower($method);
        $this->methodInfo = $this->methods[$method];

        $this->encipher = $this->getCipher(
            $method, $password, true,
            $this->randomStr($this->methodInfo[self::METHOD_INFO_IV_LEN])
        );
    }
    /**
     * 加密数据
     *
     * @param $buffer
     * @return string
     */
    public function encrypt($buffer)
    {
        if (strlen($buffer) === 0) {
            return $buffer;
        }

        $result = '';

        // 第一次发送时添加iv
        if (!$this->ivSent) {
            $this->ivSent = true;
            $result = $this->encipher->getIv();
        }

        return $result . $this->encipher->update($buffer);
    }

    /**
     * 解密数据
     *
     * @param $buffer
     * @return bool|string
     */
    public function decrypt($buffer)
    {
        if (strlen($buffer) === 0) {
            return $buffer;
        }

        if (!$this->decipher) {
            list(, $ivLen) = $this->methodInfo;

            $iv = substr($buffer, 0, $ivLen);
            $this->decipher = $this->getCipher($this->method, $this->password, false, $iv);

            $buffer = substr($buffer, $ivLen);
        }

        return $this->decipher->update($buffer);
    }

    /**
     * @param int $len
     * @return string
     */
    protected function randomStr($len = 16)
    {
        return openssl_random_pseudo_bytes($len);
    }

    /**
     * 获取混淆方式
     *
     * @param $method
     * @param $password
     * @param $isEncrypt
     * @param $iv
     * @return mixed
     */
    protected function getCipher($method, $password, $isEncrypt, $iv)
    {
        list($keyLen, $ivLen, $crytpo) = $this->methodInfo;

        if ($this->methodInfo[self::METHOD_INFO_IV_LEN] > 0) {
            list($key) = $this->EVPBytesToKey($password, $keyLen, $ivLen);
        } else {
            $key = $password;
        }

        return new $crytpo($method, $key, $isEncrypt, $iv);
    }

    /**
     * 通过password生成指定长度的key与iv
     *
     * @param $password string  密码
     * @param $keyLen   int     key长度
     * @param $ivLen    int     向量长度(Initialization Vector)
     * @return array
     * @see https://wiki.openssl.org/index.php/Manual:EVP_BytesToKey(3)
     */
    protected function EVPBytesToKey($password, $keyLen, $ivLen)
    {
        $cachedKey = sprintf('%s-%d-%d', $password, $keyLen, $ivLen);

        if (!array_key_exists($cachedKey, $this->cachedKeys)) {
            $i = 0;
            $generatedKeyData = [];

            while (strlen(join('', $generatedKeyData)) < ($keyLen + $ivLen)) {
                $data = $password;

                if ($i > 0) {
                    $data = $generatedKeyData[$i - 1] . $password;
                }

                $data = md5($data, true);
                array_push($generatedKeyData, $data);
                $i++;
            }

            $result = join('', $generatedKeyData);
            $key = substr($result, 0, $keyLen);
            $iv = substr($result, $keyLen, $ivLen);
            $this->cachedKeys[$cachedKey] = [$key, $iv];
        }

        return $this->cachedKeys[$cachedKey];
    }
}