<?php
namespace Ant\Network\Shadowsocks;

use function Amp\File\put;
use React\Socket\LimitingServer;
use React\Socket\TcpConnector;
use Evenement\EventEmitterTrait;
use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;
use Evenement\EventEmitterInterface;
use React\Socket\Server as TcpServer;
use React\Stream\Util;

/**
 * Class Server
 * @package Ant\Network\Shadowsocks
 */
class Server implements EventEmitterInterface
{
    use EventEmitterTrait;

    public function __construct(array $options)
    {

    }

    public function handleConnection($_, $fd)
    {

    }
//    protected $server;
//
//    /**
//     * @param LoopInterface $loop
//     * @param Resolver $dns
//     * @param array $options
//     */
//    public function __construct(LoopInterface $loop, Resolver $dns, array $options = [])
//    {
//        TcpRelayHandle::init($dns, new TcpConnector($loop));
//
//        $maximumConn = $options['max_connection'] ?? 1024;
//
//        $this->server = new LimitingServer(new TcpServer("tcp://0.0.0.0:{$options['port']}", $loop), $maximumConn);
//
//        $this->server->on('connection', function ($connection) use ($loop, $options) {
//            $handler = new TcpRelayHandle($connection, $loop, $options);
//
//            Util::forwardEvents($handler, $this, ['read', 'write', 'error', 'close', 'timeout']);
//        });
//    }
//
//    /**
//     * @param $name
//     * @param $arguments
//     * @return mixed
//     */
//    public function __call($name, $arguments)
//    {
//        if (!method_exists($this->server, $name)) {
//            throw new \RuntimeException();
//        }
//
//        return call_user_func_array($name, $arguments);
//    }
}