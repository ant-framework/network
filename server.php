<?php
include "vendor/autoload.php";
// 将100kb的内容分别写入 stream 跟直接赋值给一个变量
// 进行读取,写入,修改,记录使用内存与时间

// Todo Http 协议升级事件

$loop = \React\EventLoop\Factory::create();

\Ant\Coroutine\GlobalLoop::setLoop($loop);
//
//$socket = new \React\Socket\Server("tcp://0.0.0.0:8080", $loop);
//
//$http = new \React\Http\Server(function (\Psr\Http\Message\RequestInterface $request) {
//    $response = new \Ant\Http\Response(200, [
//        'Access-Control-Allow-Origin' => '*',
//        'Content-Type'  => 'application/json'
//    ]);
//
//    $headers = $request->getHeaders();
//
//    $response->getBody()->write(json_encode($headers));
//
//    return $response;
//});
//
//$http->listen($socket);

$server = \Ant\Network\Http\Server::create("tcp://0.0.0.0:8080", $loop, [], [
    'maxHeaderSize' =>  4096,
    'maxBodySize'   =>  2 * 1024 * 1024
]);

$server->on('connection', function (\React\Socket\ConnectionInterface $conn) {
    static $connTotal = 0;

    $connTotal++;

    echo "client $connTotal from " . $conn->getRemoteAddress() . "\n";
});

// 请求抵达,先将数据写入内存中,响应结束时将数据写入socket缓冲区等待写入,可以写入后,从缓冲区写入到客户端

// 写入数据,先写入缓冲区,缓冲区等待到流可写,开始写入,所以时同步写入,异步操作
// 利用协程完成,完成方式类似,先写入到匿名函数内,等待匿名函数被可写事件触发时写入,真正的写入到流后才会运行后续代码
// 差距,如果是希望写入完成且成功时,在执行后续代码时,缓冲区是无法完成的,因为写入缓冲区并不是真正的写入到流内
// Todo 异步,协程,缓冲区
$server->on('request', function (\Ant\Network\Http\Request $request, \Ant\Network\Http\Response $response) {
    if ($request->getOriginalMethod() == 'OPTIONS') {
        $response->withHeaders([
            'Access-Control-Allow-Methods' => 'GET,POST,PATCH,DELETE,PUT,HEAD,OPTIONS',
            'Access-Control-Allow-Origin' => 'http://127.0.0.1',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Headers' => 'origin, x-requested-with, content-type, accept',
        ])->end();
        return;
    }

    $response = $response->withHeaders([
        // 设置跨域信息
        'Access-Control-Allow-Origin' => '*',
        'Content-Type'  => 'application/json',
    ]);

    $response->setCookie('foo', 'bar');

//    $response->getBody()->write("Hello World");

    $response->end("Hello World");
});

$server->on('error', function (\Exception $e, \React\Socket\ConnectionInterface $socket) {
    $response = new \Ant\Http\Response(500);

    echo "Error : {$e->getMessage()} in {$e->getFile()} {$e->getLine()}", PHP_EOL;

    if ($e instanceof \Ant\Http\Exception\HttpException) {
        $response = $response
            ->withStatus($e->getStatusCode())
            ->withHeaders($e->getHeaders());
    }

    $response->getBody()->write($e->getMessage());

    // Todo web版cli程序,通过ajax请求服务端,服务端通过管道与cli通讯后返回结果给web页面
    // Todo 写完成后断开连接,如果请求未发送完成,会导致客户端判定为请求失败
    $socket->end((string) $response);
});

$loop->run();