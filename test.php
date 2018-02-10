<?php
include "vendor/autoload.php";

//function _sort($array)
//{
//    for ($i = 0, $length = count($array); $i < $length - 1; $i++) {
//        $minKey = $i;
//
//        for ($key = $i + 1; $key < $length; $key++) {
//            if ($array[$i] > $array[$key]) {
//                $minKey = $key;
//            }
//        }
//
//        if ($minKey != $i) {
//            $temp = $array[$i];
//            $array[$i] = $array[$minKey];
//            $array[$minKey] = $temp;
//        }
//    }
//
//    return $array;
//}

class Cryptor
{
    private $cipher_algo;
    private $hash_algo;
    private $iv_num_bytes;
    private $format;
    const FORMAT_RAW = 0;
    const FORMAT_B64 = 1;
    const FORMAT_HEX = 2;

    public function __construct($cipher_algo = 'aes-256-ctr', $hash_algo = 'sha256', $fmt = Cryptor::FORMAT_B64)
    {
        $this->cipher_algo = $cipher_algo;
        $this->hash_algo = $hash_algo;
        $this->format = $fmt;
        if (!in_array($cipher_algo, openssl_get_cipher_methods(true)))
        {
            throw new \Exception("Cryptor:: - unknown cipher algo {$cipher_algo}");
        }
        if (!in_array($hash_algo, openssl_get_md_methods(true)))
        {
            throw new \Exception("Cryptor:: - unknown hash algo {$hash_algo}");
        }
        $this->iv_num_bytes = openssl_cipher_iv_length($cipher_algo);
    }

    public function encryptString($in, $key, $fmt = null)
    {
        if ($fmt === null)
        {
            $fmt = $this->format;
        }
        // Build an initialisation vector
        $iv = openssl_random_pseudo_bytes($this->iv_num_bytes, $isStrongCrypto);
        if (!$isStrongCrypto) {
            throw new \Exception("Cryptor::encryptString() - Not a strong key");
        }
        // Hash the key
        $keyhash = openssl_digest($key, $this->hash_algo, true);
        // and encrypt
        $opts =  OPENSSL_RAW_DATA;
        $encrypted = openssl_encrypt($in, $this->cipher_algo, $keyhash, $opts, $iv);
        if ($encrypted === false)
        {
            throw new \Exception('Cryptor::encryptString() - Encryption failed: ' . openssl_error_string());
        }
        // The result comprises the IV and encrypted data
        $res = $iv . $encrypted;
        // and format the result if required.
        if ($fmt == Cryptor::FORMAT_B64)
        {
            $res = base64_encode($res);
        }
        else if ($fmt == Cryptor::FORMAT_HEX)
        {
            $res = unpack('H*', $res)[1];
        }
        return $res;
    }

    public function decryptString($in, $key, $fmt = null)
    {
        if ($fmt === null)
        {
            $fmt = $this->format;
        }
        $raw = $in;
        // Restore the encrypted data if encoded
        if ($fmt == Cryptor::FORMAT_B64)
        {
            $raw = base64_decode($in);
        }
        else if ($fmt == Cryptor::FORMAT_HEX)
        {
            $raw = pack('H*', $in);
        }
        // and do an integrity check on the size.
        if (strlen($raw) < $this->iv_num_bytes)
        {
            throw new \Exception('Cryptor::decryptString() - ' .
                'data length ' . strlen($raw) . " is less than iv length {$this->iv_num_bytes}");
        }
        // Extract the initialisation vector and encrypted data
        $iv = substr($raw, 0, $this->iv_num_bytes);
        $raw = substr($raw, $this->iv_num_bytes);
        // Hash the key
        $keyhash = openssl_digest($key, $this->hash_algo, true);
        // and decrypt.
        $opts = OPENSSL_RAW_DATA;
        $res = openssl_decrypt($raw, $this->cipher_algo, $keyhash, $opts, $iv);
        if ($res === false)
        {
            throw new \Exception('Cryptor::decryptString - decryption failed: ' . openssl_error_string());
        }
        return $res;
    }

    public static function Encrypt($in, $key, $fmt = null)
    {
        $c = new Cryptor();
        return $c->encryptString($in, $key, $fmt);
    }

    public static function Decrypt($in, $key, $fmt = null)
    {
        $c = new Cryptor();
        return $c->decryptString($in, $key, $fmt);
    }
}



//$loop = \React\EventLoop\Factory::create();
//
//$deferred = new \React\Promise\Deferred();
//
//$promise = new React\Promise\Promise(function ($resolve) use ($loop) {
//    $loop->addTimer(2, function () use ($resolve) {
//        $resolve();
//    });
//});
//
//$promise->then(function () use ($loop) {
//    echo "Complete";
//})->then(function () {
//    echo 123;
//})->then(function () {
//    echo 456;
//    return 123;
//});
//
//$loop->run();


//$timerId = 2994 ^ 545;
//
//$list = new SplQueue();
//
//for ($i = 1; $i <= 10; $i++) {
//    $list->push($i);
//}
//
//$list->rewind();
//
//$list->offsetUnset(4);
//
//while (!$list->isEmpty()) {
//    echo $list->dequeue();
//}

//$star = microtime(true);
//
//echo (microtime(true) - $star) * 1000;


//$socket = stream_socket_client("tcp://127.0.0.1:8080");

//stream_set_blocking($socket, 0);

//fwrite($socket, "GET /test HTTP/1.1\r\n\r\n");
//
//echo fread($socket, 8192);

// 将100kb的内容分别写入 stream 跟直接赋值给一个变量
// 进行读取,写入,修改,记录使用内存与时间

//$loop = \React\EventLoop\Factory::create();

//$http = new \React\Http\Server($server);
//
//$http->on('request', function (\React\Http\Request $request, \React\Http\Response $response) {
//    $response->writeHead(200, array('Content-Type' => 'text/plain'));
//    $response->end("Hello world!\n");
//});

//$jsonRpc = Ant\Network\JsonRpc\Server::create("tcp://0.0.0.0:80", $loop);
//
//$jsonRpc->on('connection', function () {
//    echo "new connection\r\n";
//});
//
//$jsonRpc->on('request', function ($method, $args, $id, \Ant\Network\JsonRpc\Response $res) {
//    if (!function_exists($method)) {
//        throw new \BadFunctionCallException("{$method} not found");
//    }
//
//    $result = call_user_func_array($method, $args);
//
//    $res->withId($id)->withResult($result)->end();
//});
//
///**
// * 检查是否是自幂数
// *
// * @param $num
// * @return bool
// */
//function selfPower($num)
//{
//    if (($len = strlen($num)) > 9) {
//        throw new \RuntimeException("位数不能超过9位");
//    }
//
//    if (!is_int($num)) {
//        return false;
//    }
//
//    $temp = 0;
//    $pos = $len;
//
//    while ($pos--) {
//        $temp += (int)(substr($num, $pos, 1)) ** $len;
//    }
//
//    return $temp === $num;
//}
//
//$loop->run();
