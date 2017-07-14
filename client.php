<?php


require 'vendor/autoload.php';

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

$loop->run();
//$loop->addPeriodicTimer(3, function () use ($connPool) {
//    $startTime = microtime(true);
//
//    foreach ($connPool as $index => $socket) {
//        if ($socket->isTimeout()) {
//            $socket->emit('timeout', [$socket]);
//            unset($connPool[$index]);
//        }
//    }
//
//    echo (microtime(true) - $startTime) * 1000;
//
//    var_dump(count($connPool));
//});

//$loop->run();
