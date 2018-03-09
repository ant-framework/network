<?php
namespace Ant\Network\Shadowsocks;

use Evenement\EventEmitterTrait;
use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;
use Evenement\EventEmitterInterface;
use React\Socket\ConnectionInterface;
use React\Socket\TcpConnector;
use React\Socket\Server as TcpServer;
use React\Stream\DuplexResourceStream;
use RuntimeException;
use InvalidArgumentException;

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

    public function listen($uri, array $context = [])
    {
        $server = new TcpServer($uri, $this->loop);

        $server->on('connection', [$this, "handleConnection"]);
    }

    /**
     * @param $connection
     */
    public function handleConnection($connection)
    {
        static $first = true;

        if ($first === false) {
            return;
        }
        $first = false;

        $socket = new Connection($connection, $this->loop, $this->options);

        new TcpRelayHandle($this->connector, $this->dns, $socket);
    }
}