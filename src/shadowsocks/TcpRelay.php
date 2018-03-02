<?php
namespace Ant\Network\Shadowsocks;

use Evenement\EventEmitterTrait;
use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;
use Evenement\EventEmitterInterface;
use React\Socket\ConnectionInterface;
use React\Socket\TcpConnector;
use React\Socket\Server as TcpServer;

/**
 * todo 检查必须参数
 * required
 *  server_addr
 *  server_port
 *  timeout
 *  password
 *  method
 *
 * Class TcpRelay
 * @package Ant\Network\Shadowsocks
 */
class TcpRelay implements EventEmitterInterface
{
    use EventEmitterTrait;

    protected $loop;

    protected $dns;

    protected $options = [];

    protected $connector;

    /**
     * @param LoopInterface $loop
     * @param Resolver $dns
     * @param array $options
     */
    public function __construct(LoopInterface $loop, Resolver $dns, array $options = [])
    {
        $this->loop = $loop;
        $this->dns = $dns;
        $this->connector = new TcpConnector($this->loop);
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @param $uri
     */
    public function listen($uri)
    {
        $server = new TcpServer($uri, $this->loop);

        $server->on('connection', [$this, "handleConnection"]);
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function handleConnection(ConnectionInterface $connection)
    {
        $socket = new Connection($connection, $this->loop, $this->options);

        new TcpRelayHandle($this->connector, $this->dns, $socket);
    }
}