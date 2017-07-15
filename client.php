<?php
require 'vendor/autoload.php';

ini_set("memory_limit", "512M");

//$client = stream_socket_client("tcp://127.0.0.1:8080");
//$client = stream_socket_client("tcp://120.76.205.180:8777");

//$request = new \Ant\Http\Request('GET', "http://120.76.205.180:8777");

//$request = $request->withHeaders([
//    'Content-Type'  =>  'application/json',
//    'Cookie'        =>  'token=foobar',
//    'Connection'    =>  'keep-alive',
//    'Accept'        =>  'application/json',
//]);

//$request->getBody()->write(json_encode(['foo' => 'bar']));

//fwrite($client, (string) $request);

//echo fread($client, 8192);

//fwrite($client, (string) $request);

//fwrite($client, 'foobar');

//fclose($client);

$loop = \React\EventLoop\Factory::create();

$timers = [];
for ($i = 0; $i < 200000; $i++) {
    $timers[] = $loop->addTimer(59, function () {});
}

while ($timer = array_shift($timers)) {
    $loop->cancelTimer($timer);
}

$loop->futureTick(function (\React\EventLoop\LoopInterface $loop) {
    $start = microtime(true);

    $loop->addTimer(59, function () use ($start) {
        echo (microtime(true) - $start) * 1000;
    });
});

$loop->run();
