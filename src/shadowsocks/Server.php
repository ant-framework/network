<?php
namespace Ant\Network\Shadowsocks;

use React\Socket\LimitingServer;
use React\Socket\TcpConnector;
use Evenement\EventEmitterTrait;
use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;
use Evenement\EventEmitterInterface;
use React\Socket\Server as TcpServer;
use React\Stream\Util;

/**
 * todo 检查必须参数
 * required
 *  server_addr
 *  server_port
 *  timeout
 *  password
 *  method
 *
 * Class Server
 * @package Ant\Network\Shadowsocks
 */
class Server implements EventEmitterInterface
{
    use EventEmitterTrait;

    protected $maximumConn;

    /**
     * @param LoopInterface $loop
     * @param Resolver $dns
     * @param array $options
     */
    public function __construct(LoopInterface $loop, Resolver $dns, array $options = [])
    {
        TcpRelayHandle::init($dns, new TcpConnector($loop));

        $this->maximumConn = $options['max_connection'] ?? 1024;

        $server = new LimitingServer(new TcpServer("tcp://0.0.0.0:{$options['port']}", $loop), $this->maximumConn);

        $server->on('connection', function ($connection) use ($loop, $options) {
            $handler = new TcpRelayHandle($connection, $loop, $options);

            Util::forwardEvents($handler, $this, ['read', 'write', 'error', 'close', 'timeout']);
        });
    }
}