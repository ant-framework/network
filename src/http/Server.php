<?php
namespace Ant\Network\Http;

use Evenement\EventEmitter;
use React\Socket\ServerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;

/**
 * Todo 在指定时间内没有完成传输,响应超时,并清空缓冲区数据
 * Todo 无法判断body响应411
 * Todo 100-continue 417
 * Todo 实现HTTP/1.1保持长链接
 * Todo 协议升级事件,在收到Upgrade头后,进行协议升级,升级完成后该客户端的输入交由另一个协议进行处理
 * Todo 致命错误与无法catch的错误,处理完后,重启服务器
 * Todo 检查长链接是否稳定
 * Todo Connection Timeout,指定时间内未完成,则视为超时,断开连接并响应超时错误
 *
 * @event
 * connection   建立一个新的tcp链接
 * error        客户端错误事件
 * close        服务器连接断开事件
 * request      请求完全抵达事件
 * upgrade      协议升级事件
 *
 * 保持长链接,需求
 * 分包,保证每次Http请求都不应该影响下一个解析下一个请求
 * 识别错误数据,如果Http协议解析失败,响应错误
 * 半双工,在响应前不应该接受新请求
 *
 * 错误等级
 * PHP5的错误,如语法错误等致命错误跟无法catch的错误,停止脚本
 * 解析错误,缓冲区溢出,客户端错误,直接断开连接,停止接收数据
 * 应用程序错误,如参数缺失,数据不存在等,属于业务逻辑错误,不应该断开连接
 *
 * Class Server
 * @package Ant\Network\Http
 */
class Server extends EventEmitter
{
    protected $options = [
        'maxHeaderSize' =>  4096,
        'maxBodySize'   =>  2097152,
        'timeout'       =>  30
    ];

    public static function create($uri, LoopInterface $loop, array $context = [], array $options = [])
    {
        $ioServer = new \React\Socket\TcpServer($uri, $loop, $context);

        return new static($ioServer, $options);
    }

    public function __construct(ServerInterface $io, $options = [])
    {
        $this->options = array_merge($this->options, $options);

        $io->on('connection', [$this, 'handleConnection']);
    }

    public function handleConnection(ConnectionInterface $socket)
    {
        // Todo KeepAlive时间
        // Todo 超时机制,指定时间没完成,触发回调关闭连接
        // Todo 响应超时,客户端主动断开连接,不再写入
        $this->emit('connection', [$socket]);

        $buffer = new RequestBuffer(
            $socket,
            $this->options['maxHeaderSize'],
            $this->options['maxBodySize']
        );

        $buffer->on('data', function (Request $request) use ($socket) {
            $this->handleRequest($request, $socket);
        });

        $buffer->on('error', function (\Exception $e) use ($socket, $buffer) {
            // 如果没有处理异常,直接断开连接
            if ($this->listeners('error')) {
                $this->emit('error', [$e, $socket]);
            } else {
                $socket->close();
            }
        });
    }

    public function handleRequest(Request $request, ConnectionInterface $socket)
    {
        $response = Response::prepare($socket, $request);
        // Todo 协议升级事件
        // Todo 通过Connection判断是否保持长链接
        // Todo 检查主机名
        // Todo 检查是否为合法的Http请求
        // Todo 兼容Ioc容器
        $this->emit('request', [$request, $response]);
    }
}