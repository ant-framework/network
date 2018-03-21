<?php
namespace Ant\Network\Shadowsocks;

use React\Promise;
use Evenement\EventEmitter;
use React\Socket\TcpConnector;
use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connection as Socket;

class TcpRelayHandle extends EventEmitter
{
    const STAGE_CONNECTING = 1;
    const STAGE_STREAM = 2;

    const ADDRTYPE_IPV4 = 0x01;
    const ADDRTYPE_IPV6 = 0x04;
    const ADDRTYPE_HOST = 0x03;
    const ADDRTYPE_AUTH = 0x10;
    const ADDRTYPE_MASK = 0xF;

    /**
     * @var int
     */
    protected $stage = self::STAGE_CONNECTING;

    /**
     * @var Connection
     */
    protected $clientSocket;

    /**
     * @var Socket
     */
    protected $remoteSocket;

    /**
     * @var bool
     */
    protected static $init = false;

    /**
     * @var Resolver
     */
    protected static $dns;

    /**
     * @var TcpConnector
     */
    protected static $connector;

    /**
     * 初始化异步dns
     *
     * @param Resolver $dns
     * @param TcpConnector $connector
     */
    public static function init(Resolver $dns, TcpConnector $connector)
    {
        self::$dns = $dns;
        self::$connector = $connector;
    }

    public function __construct(ConnectionInterface $conn, LoopInterface $loop, array $options)
    {
        $clientSocket = new Connection($conn, $loop, $options);
        $clientSocket->on('data', [$this, 'handleClientData']);
        $clientSocket->on('close', [$this, 'handleClose']);
        $clientSocket->setTimeout($options['timeout'] ?? 30, [$this, 'handleTimeout']);

        $this->clientSocket = $clientSocket;
    }

    /**
     * 处理客户端数据
     *
     * @param $data
     */
    public function handleClientData($data)
    {
        try {
            if ($this->stage === self::STAGE_CONNECTING) {
                $this->handleStageConnecting($data);
            } elseif ($this->stage === self::STAGE_STREAM) {
                $this->remoteSocket->write($data);
            }
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }

    /**
     * 解析客户端目标并与其建立连接
     *
     * @param $data
     */
    public function handleStageConnecting($data)
    {
        list($addressType, $host, $port, $headerLength) = $this->parseHeader($data);

        if (empty($host) || empty($port)) {
            $this->clientSocket->close();
            return;
        }

        // 暂停读取客户端数据,直到远程连接建立
        $this->clientSocket->pause();
        $data = substr($data, $headerLength);

        $this
            ->resolveHostname($addressType, $host)
            ->then(function ($address) use ($port) {
                return $this->createSocketForAddress($address, $port);
            }, [$this, 'handleError'])
            ->then(function (Socket $socket) use ($data) {
                $socket->once('close', [$this, 'handleClose']);
                $socket->on('data', [$this, 'handleRemoteData']);

                $this->remoteSocket = $socket;
                $this->remoteSocket->write($data);

                // 在于远程建立连接之后再继续读取客户端内容
                $this->clientSocket->resume();
                $this->stage = self::STAGE_STREAM;
            }, [$this, 'handleError']);
    }

    /**
     * @param $data
     */
    public function handleRemoteData($data)
    {
        $this->clientSocket->write($data);
    }

    /**
     * @param \Throwable $e
     */
    public function handleError(\Throwable $e)
    {
        $this->emit('error', [$e]);

        $this->handleClose();
    }

    /**
     * 关闭连接
     */
    public function handleClose()
    {
        $this->emit('close');

        $this->clientSocket->end();

        if ($this->remoteSocket) {
            $this->remoteSocket->close();
        }
    }

    public function handleTimeout()
    {
        $this->emit('timeout');
        $this->handleClose();
    }

    /**
     * [1-byte 地址类型][host长度(只有类型为host时才有) 主机名(ipv4,host,ipv6)][2-byte大端位 端口]
     *
     * @param $data
     * @return array|bool
     */
    protected function parseHeader($data)
    {
        $addressType = ord($data{0});
        $address = '';
        $port = '';
        $headerLength = 0;

        // 转换为16进制
        switch ($addressType & self::ADDRTYPE_MASK) {
            // 4-byte的ipv4地址
            case self::ADDRTYPE_IPV4:
                $headerLength = 7;
                if (strlen($data) >= $headerLength) {
                    $address = join('.', $this->toBytes(substr($data, 1, 4)));
                    $port = substr($data, 5, 2);
                }
                break;
            // 1~255-byte的域名
            case self::ADDRTYPE_HOST:
                $len = strlen($data);
                if ($len > 2) {
                    $addressLength = ord($data{1});
                    $headerLength = $addressLength + 4;

                    if ($len >= $headerLength) {
                        $address = substr($data, 2, $addressLength);
                        $port = substr($data, 2 + $addressLength, 2);
                    }
                }
                break;
            // 16-byte的ipv6地址,暂不支持ipv6地址
            case self::ADDRTYPE_IPV6:
                throw new \InvalidArgumentException('ipv6 error');
                break;
            default:
                throw new \InvalidArgumentException("unsupported address type {$addressType}");
        }

        if (!$address) {
            throw new \RuntimeException('header parse error');
        }

        return [$addressType, $address, unpack('n*', $port)[1], $headerLength];
    }

    /**
     * @param $buffer
     * @return array
     */
    protected function toBytes($buffer)
    {
        $bytes = [];
        for ($i = 0; $i < strlen($buffer); $i++) {
            $bytes[] = ord($buffer{$i});
        }

        return $bytes;
    }

    /**
     * 解析dns
     *
     * @param $addressType
     * @param $host
     * @return null|Promise\FulfilledPromise|Promise\Promise
     */
    protected function resolveHostname($addressType, $host)
    {
        if (self::ADDRTYPE_HOST === ($addressType & self::ADDRTYPE_MASK)) {
            return self::$dns->resolve($host);
        }

        return Promise\resolve($host);
    }

    /**
     * 连接远程目标
     *
     * @param $address
     * @param $port
     * @return mixed
     */
    protected function createSocketForAddress($address, $port)
    {
        return self::$connector->connect($this->getSocketUrl($address, $port));
    }

    /**
     * @param $address
     * @param $port
     * @return string
     */
    protected function getSocketUrl($address, $port)
    {
        if (strpos($address, ':') !== false) {
            $address = '[' . $address . ']';
        }

        return sprintf('tcp://%s:%s', $address, $port);
    }
}