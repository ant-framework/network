<?php
include "vendor/autoload.php";

function _sort($array)
{
    for ($i = 0, $length = count($array); $i < $length - 1; $i++) {
        $minKey = $i;

        for ($key = $i + 1; $key < $length; $key++) {
            if ($array[$i] > $array[$key]) {
                $minKey = $key;
            }
        }

        if ($minKey != $i) {
            $temp = $array[$i];
            $array[$i] = $array[$minKey];
            $array[$minKey] = $temp;
        }
    }

    return $array;
}

function foobar($i)
{
    if ($i > 0) {
        return false;
    }

    return true;
}

// 业务逻辑复杂时,需要经常判断是否出错,并且进行处理
$result = foobar(1);
if ($result === false) {
    // 错误处理
}

// 正确处理

try {
    // 在这里专心的处理正常的逻辑,不需要关注出错后的逻辑
    // 并且业务逻辑复杂时,少了很多错误处理的判断,提高的代码可读性
} catch (\Exception $e) {
    // 在这里统一处理错误
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
