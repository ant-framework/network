<?php

require "vendor/autoload.php";

if (!file_exists('config.json')) {
    exit('配置文件不存在');
}

$config = json_decode(file_get_contents('config.json'), true);

$loop = \React\EventLoop\Factory::create();

$factory = new React\Dns\Resolver\Factory();

$dns = $factory->create($config['dns_server'], $loop);

foreach ($config['servers'] as $serverOptions) {
    $server = new \Ant\Network\Shadowsocks\Server($loop, $dns, $serverOptions);

    $server->on('close', function () {
        echo 'connection close', PHP_EOL;
    });
    $server->on('timeout', function () {
        echo 'connection timeout', PHP_EOL;
    });
}

$loop->addPeriodicTimer(5, function () {
    // todo 实时监控内存
    $memory = memory_get_usage() / 1024;
    $formatted = number_format($memory, 3).'K';
    echo "Current memory usage: {$formatted}\n";
});

$loop->run();