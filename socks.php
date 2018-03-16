<?php

require "vendor/autoload.php";

$loop = \React\EventLoop\Factory::create();

$factory = new React\Dns\Resolver\Factory();

$dns = $factory->create('119.29.29.29', $loop);

$server = new \Ant\Network\Shadowsocks\Server($loop, $dns, [
    'method'    =>  'aes-256-cfb',
    'password'  =>  'qwe!123'
]);

$server->listen('0.0.0.0:8080');

$loop->run();