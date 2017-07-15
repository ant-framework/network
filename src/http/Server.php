<?php
namespace Ant\Network\Http;

use Evenement\EventEmitterTrait;
use React\Socket\ServerInterface;
use React\EventLoop\LoopInterface;
use Evenement\EventEmitterInterface;
use React\Socket\ConnectionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Todo 100-continue 417
 * Todo 致命错误与无法catch的错误,处理完后,重启服务器
 * Todo 检查长链接是否稳定
 * Todo Connection Timeout,指定时间内未完成,则视为超时,断开连接并响应超时错误
 * Todo 兼容Ioc容器
 *
 * @event
 * connection   建立一个新的tcp链接
 * error        客户端错误事件
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
        $conn = new Connection($socket, $this->loop, $this->options['keepAliveTimeout']);
        // 指定时间内没有数据抵达将会自动断开连接
        $conn->on('timeout', [$conn, 'close']);

        $this->emit('connection', [$conn]);

        $buffer = new HttpParser(
            $conn,
            $this->options['maxHeaderSize'],
            $this->options['maxBodySize']
        );

        $buffer->on('data', function (ServerRequestInterface $request) use ($conn) {
            $this->handleRequest($request, $conn);
        });

        $buffer->on('error', function (\Exception $e) use ($conn) {
            // 如果没有处理异常,直接断开连接
            if ($this->listeners('error')) {
                $this->emit('error', [$e, $conn]);
            } else {
                $conn->close();
            }
        });
    }

    /**
     * @param ServerRequestInterface $request
     * @param ConnectionInterface $socket
     */
    protected function handleRequest(ServerRequestInterface $request, ConnectionInterface $socket)
    {
        $response = Response::prepare($socket, $request);

        // 客户端要求升级协议,http服务器将不再处理此链接
        if ($request->hasHeader('Upgrade')) {
            $socket->removeAllListeners();
            $this->emit('upgrade', [$request, $socket]);
            return;
        }

        // Todo 检查是否为合法的Http请求
        $this->emit('request', [$request, $response]);
    }
}