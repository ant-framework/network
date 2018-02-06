<?php
namespace Ant\Network\Http;

use Ant\Http\Exception\HttpException;
use Evenement\EventEmitterTrait;
use React\Socket\ServerInterface;
use React\EventLoop\LoopInterface;
use Evenement\EventEmitterInterface;
use React\Socket\ConnectionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Todo 致命错误与无法catch的错误,处理完后,重启服务器
 * Todo 兼容Ioc容器
 *
 * @event
 * connection   建立一个新的tcp链接
 * clientError  客户端错误事件
 * request      请求完全抵达事件
 * upgrade      协议升级事件
 *
 * 错误等级
 * PHP5的错误,如语法错误等致命错误跟无法catch的错误,停止脚本
 * 解析错误,缓冲区溢出,客户端错误,直接断开连接,停止接收数据
 * 应用程序错误,如参数缺失,数据不存在等,属于业务逻辑错误,不应该断开连接
 *
 * Class Server
 * @package Ant\Network\Http
 */
class Server implements EventEmitterInterface
{
    use EventEmitterTrait;

    protected $options = [
        'maxHeaderSize'     =>  4096,
        'maxBodySize'       =>  2097152,
        'keepAliveTimeout'  =>  10
    ];

    /**
     * @param LoopInterface $loop
     * @param array $options
     */
    public function __construct(LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;

        $this->options = array_merge($this->options, $options);
    }

    /**
     * @param ServerInterface $io
     */
    public function listen(ServerInterface $io)
    {
        $io->on('connection', [$this, 'handleConnection']);
    }

    /**
     * @param ConnectionInterface $socket
     */
    public function handleConnection(ConnectionInterface $socket)
    {
        $conn = new Connection($socket, $this->loop);
        // 提供支持超时的Connection给客户端
        $this->emit('connection', [$conn]);

        if (!$conn->listeners('timeout')) {
            // 指定时间内没有数据抵达将会自动断开连接
            $conn->setTimeout($this->options['keepAliveTimeout'], [$conn, 'close']);
        }

        // 创建http缓冲区
        $buffer = new HttpBuffer($conn, $this->options['maxHeaderSize'], $this->options['maxBodySize']);

        $buffer->on('complete', [$this, 'handleRequest']);

        $buffer->on('clientError', [$this, 'handleError']);
    }

    /**
     * Todo 检查是否为合法的Http请求
     * @param ServerRequestInterface $request
     * @param ConnectionInterface $socket
     */
    public function handleRequest(ServerRequestInterface $request, ConnectionInterface $socket)
    {
        $response = Response::prepare($socket, $request);

        if ($request->hasHeader('Expect')) {
            if (empty($this->listeners['checkExpectation'])) {
                throw new HttpException(417, "Expectation Failed");
            }

            $this->emit('checkExpectation', [$request, $response]);
            return;
        }

        // Todo 无人监听,响应 400或426
        if ($request->hasHeader('Upgrade')) {
            // 客户端要求升级协议,http服务器将不再处理此链接
            $socket->removeAllListeners();
            $this->emit('upgrade', [$request, $socket]);
            return;
        }

        $this->emit('request', [$request, $response]);
    }

    /**
     * @param \Exception $e
     * @param ConnectionInterface $socket
     */
    public function handleError(\Exception $e, \React\Socket\ConnectionInterface $socket)
    {
        if (isset($this->listeners['clientError'])) {
            $this->emit('clientError', [$e, $socket]);
            return;
        }

        $response = new \Ant\Http\Response(500);

        if ($e instanceof \Ant\Http\Exception\HttpException) {
            $response = $response
                ->withStatus($e->getStatusCode())
                ->withHeaders($e->getHeaders());
        }

        $response->getBody()->write($e->getMessage());

        $socket->end((string) $response);
    }
}