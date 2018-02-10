<?php
namespace Ant\Network\Shadowsocks;

use Ant\Http\Response;
use Evenement\EventEmitterTrait;
use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;
use Evenement\EventEmitterInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Server as TcpServer;
use React\Socket\TcpConnector;

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
    const STAGE_INIT = 1;
    const STAGE_CONNECTING = 2;
    const STAGE_STREAM = 3;

    use EventEmitterTrait;

    protected $loop;

    protected $dns;

    protected $options = [];

    /**
     * @param LoopInterface $loop
     * @param Resolver $dns
     * @param array $options
     */
    public function __construct(LoopInterface $loop, Resolver $dns, array $options = [])
    {
        $this->loop = $loop;
        $this->dns = $dns;
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

        $socket->on('data', [$this, 'handleData']);
    }

    public function handleData($data, Connection $socket)
    {
        $addressType = ord($data{0});
        $address = substr($data, 1, -2);
        $port = substr($data, -2);
        $connector = new TcpConnector($this->loop);

        switch ($addressType) {
            // 4-byte的ipv4地址
            case 0x01:

                break;
            // 1~255-byte的域名
            case 0x03:

                break;
            // 16-byte的ipv6地址
            case 0x04:

                break;
        }
    }

    protected function parseData()
    {

    }
}