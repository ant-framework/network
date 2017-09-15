<?php

require "vendor/autoload.php";

$loop = \React\EventLoop\Factory::create();

$server = new \Ant\Network\Socks\Server($loop);

$server->on('connection', function (\Ant\Network\Socks\Connection $socket) {
});

$server->listen("tcp://0.0.0.0:8080");

$loop->run();