<?php
include "vendor/autoload.php";

$loop = \React\EventLoop\Factory::create();

$server = new \Ant\Network\Http\Server($loop, [
    'maxHeaderSize' =>  4096,
    'maxBodySize'   =>  2 * 1024 * 1024,
]);

$server->on('request', function (\Ant\Http\ServerRequest $request, \Ant\Network\Http\Response $response) {
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
        ]);

        $response->getBody()->write(json_encode($request->getHeaders()));
    }

    $response->end();
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

    $socket->end((string) $response);
});

$server->listen(new \React\Socket\Server(8080, $loop));

$loop->run();
