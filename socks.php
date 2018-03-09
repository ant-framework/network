<?php

require "vendor/autoload.php";

$loop = \React\EventLoop\Factory::create();

$factory = new React\Dns\Resolver\Factory();

$dns = $factory->create('8.8.8.8', $loop);

$server = new \Ant\Network\Shadowsocks\Server($loop, $dns, [
    'method'    =>  'aes-256-cfb',
    'password'  =>  '123'
]);

$server->listen('0.0.0.0:8080');

$loop->run();