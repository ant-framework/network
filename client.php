<?php
require 'vendor/autoload.php';

$client = new Swoole\Client(SWOOLE_TCP | SWOOLE_ASYNC); //异步非阻塞

$client->on("connect", function($cli) {
//    $request = new \Ant\Http\Request('GET', 'http://www.baidu.com', ['accept' => 'text/html']);
//    $data = (string) $request;
//    $cli->send($data);
    $cli->send(12);
});

$client->on("receive", function($cli, $data = ""){
    var_dump($data);
});

$client->on("close", function($cli){
    echo "close\n";
});

$client->on("error", function($cli){
    exit("error\n");
});

$client->connect('127.0.0.1', 8847, 30);


//ini_set("memory_limit", "512M");
//
//$host = "https://sp0.baidu.com/5a1Fazu8AA54nxGko9WTAnF6hhy/su?wd=&json=1&p=3&sid=1434_25549_21088_17001_22075&req=2&csor=0&cb=jQuery110204802594471653818_1521107741110&_=1521107741111";
//
//$client = stream_socket_client("tcp://127.0.0.1:80");
//
//$request = new \Ant\Http\Request('GET', "http://127.0.0.1/test.php");
//
//$request = $request->withHeaders([
//    'Content-Type'  =>  'application/json',
//    'Cookie'        =>  'token=foobar',
//    'Connection'    =>  'keep-alive',
//    'Accept'        =>  'application/json',
//]);
//
//fwrite($client, (string) $request);
//
//echo fread($client, 8192);
//
//fwrite($client, (string) $request);
//
//fwrite($client, 'foobar');
//
//fclose($client);

// 保持不变性测试,性能,内存消耗
//$response = new \Ant\Http\Response();

// 要保证在中间件中传递的时候,能够实时刷新

/*
app = new Application();

app.loadConfig('config.json');

app.use(function (ctx, next) {
    try {
        yield next;
    } catch (\Exception e) {
        handleError(e, ctx.req, ctx.res);
    }
});

app.use(function (ctx, next) {
    start = time();
    yield next;
    ctx.res.withHeader('X-Run-Time', time() - start);
});

app.use(function (ctx, next) {
    token = ctx.req.getCookie('token');
    ctx.userInfo = JWT.decode(token);
    yield next;
});

app.use(function (ctx, next) {
    redis.get(ctx.userInfo.id);

    ctx.res.withHeader('Location', 'http://www.baidu.com');

    ctx.res.getBody.write('hello world');
});

app.run();
*/

//$loop = React\EventLoop\Factory::create();
//
//$factory = new Factory();
//$resolver = $factory->create('8.8.8.8', $loop);
//
//$name = isset($argv[1]) ? $argv[1] : 'ant.com';
//
//$resolver->resolve($name)->then(function ($ip) use ($name) {
//    echo 'IP for ' . $name . ': ' . $ip . PHP_EOL;
//}, 'printf');
//
//$loop->run();

//$client = new Client($loop);
//
//$request = $client->request('GET', 'http://127.0.0.1');
//
//$request->on('response', function (Response $response) {
//    var_dump($response->getHeaders());
//
//    $response->on('data', function ($chunk) {
//        echo $chunk;
//    });
//
//    $response->on('end', function () {
//        echo 'DONE' . PHP_EOL;
//    });
//});
//
//$request->on('error', function (\Exception $e) {
//    echo $e;
//});
//
//$request->end();
