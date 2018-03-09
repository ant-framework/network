<?php
include "vendor/autoload.php";

$encipher = new \Ant\Network\Shadowsocks\StreamEncryption('123', 'aes-256-cfb');
//$encipher = new Encryptor('123', 'aes-256-cfb');

$content = explode("\r\n===================\r\n", file_get_contents('input.log'));

foreach ($content as $value) {
    var_dump($encipher->decrypt($value));
}



//$socket = stream_socket_client('tcp://14.215.177.38:80');
//$socket = stream_socket_client('tcp://45.120.159.61:8043');
//$socket = stream_socket_client('tcp://127.0.0.1:8080');
//
//$req = new \Ant\Http\Request('GET', 'http://blog.csdn.net/shagoo/article/details/6396089');
//
//$port = file_get_contents('test.log');
//
//$header = chr(3) . chr(strlen('blog.csdn.net')) . 'blog.csdn.net' . $port;
//
//fwrite($socket, $encipher->encrypt($header . $req));
//
//stream_set_blocking($socket, false);
//
//$readStream = [$socket];
//
//$length = 0;
//
//while (true) {
//    if (false === @stream_select($readStream, $writeStream, $except, null)) {
//        continue;
//    }
//
//    foreach ($readStream as $stream) {
//        $data = stream_get_contents($stream);
//
////        $length += strlen($data);
//
////        var_dump(strlen($data));
//        echo $encipher->decrypt($data);
//    }
//}


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


//var_dump(hexdec());
//$str = '，';
//for ($i = 0; $i < 3; $i++) {
//    var_dump(dechex(ord($str{$i})));
//}
//var_dump();

//echo chr(0x8c), PHP_EOL;

//$result = file_get_contents('test.log');

//$result = pack('H*', 80);
//var_dump($result);
//var_dump(hexdec(unpack('H*', $result)[1]));
//for ($i = 0; $i < strlen($result); $i++) {
//    echo ord($result{$i}), PHP_EOL;
//}

//$result = pack('C*', 443);
//
//var_dump($result);
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
