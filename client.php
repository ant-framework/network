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


// 保持不变性测试,性能,内存消耗
$response = new \Ant\Http\Response();

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
