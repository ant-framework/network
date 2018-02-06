<?php
include "vendor/autoload.php";

$loop = \React\EventLoop\Factory::create();

$server = new \Ant\Network\Http\Server($loop, [
    'maxHeaderSize' =>  4096,
    'maxBodySize'   =>  2 * 1024 * 1024,
]);

$server->on('connection', function (\Ant\Network\Http\Connection $conn) {
    $conn->setTimeout(5, function ($conn) {
        echo 'client timeout', PHP_EOL;
        $conn->close();
    });
});

$server->on('request', function (\Ant\Http\ServerRequest $request, \Ant\Network\Http\Response $response) use ($loop) {
    if ($request->getOriginalMethod() == 'OPTIONS') {
        $response = $response->withHeaders([
            'Access-Control-Allow-Methods' => 'GET,POST,PATCH,DELETE,PUT,HEAD,OPTIONS',
            'Access-Control-Allow-Origin' => 'http://127.0.0.1',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Headers' => 'origin, x-requested-with, content-type, accept',
        ]);
    } else {
        $response = $response->withHeaders([
            // 设置跨域信息
            'Access-Control-Allow-Origin' => '*',
            'Content-Type'  => 'application/json',
            'Cache-Control' => 'max-age=3600'
        ]);

        $response->getBody()->write(var_export($request->getHeaders(), true));
        $response = $response->bodyConvertToStream();
    }

    // todo network库参考node
    // todo 框架实现参考koa
    // todo 流式响应
    $response->end();
});

$server->listen(new \React\Socket\Server(8080, $loop));

$loop->run();
