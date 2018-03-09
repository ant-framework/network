<?php
namespace Ant\Network\Shadowsocks;

use React\Promise;
use React\Socket\TcpConnector;
use React\Dns\Resolver\Resolver;
use React\Socket\Connection as Socket;

class TcpRelayHandle
{
    const STAGE_CONNECTING = 1;
    const STAGE_STREAM = 2;

    const ADDRTYPE_IPV4 = 0x01;
    const ADDRTYPE_IPV6 = 0x04;
    const ADDRTYPE_HOST = 0x03;
    const ADDRTYPE_AUTH = 0x10;
    const ADDRTYPE_MASK = 0xF;

    const UP_STREAM_BUF_SIZE = 16 * 1024;
    const DOWN_STREAM_BUF_SIZE = 32 * 1024;

    protected $stage = self::STAGE_CONNECTING;

    protected $loop;

    protected $dns;

    protected $clientSocket;

    /**
     * @var Socket
     */
    protected $remoteSocket;

    public function __construct(
        TcpConnector $connector,
        Resolver $dns,
        Connection $clientSocket
    ) {
        $this->connector = $connector;
        $this->dns = $dns;
        $this->clientSocket = $clientSocket;

        $this->clientSocket->once('data', [$this, 'handleClientData']);
    }

    public function handleClientData($data)
    {
        try {
            if ($this->stage === self::STAGE_CONNECTING) {
                $this->handleStageConnecting($data);
            } elseif ($this->stage === self::STAGE_STREAM) {
                $this->writeToRemote($data);
            }
        } catch (\Throwable $e) {
            // todo logging
            // todo handle exception
        }
    }

    public function handleStageConnecting($data)
    {
        $headerResult = $this->parseHeader($data);

        if (!$headerResult) {
            throw new \RuntimeException();
        }

        list($addressType, $hostname, $port, $headerLength) = $headerResult;

        $data = substr($data, $headerLength);

        // todo 取消dns
        $this->resolveHostname($addressType, $hostname)
            ->then(function ($address) use ($port) {
                return $this->createSocketForAddress($address, $port);
            })
            ->then(function (Socket $socket) use ($data) {
                $this->remoteSocket = $socket;
                $this->remoteSocket->on('data', [$this, 'handleRemoteData']);
                $this->stage = self::STAGE_STREAM;
                $this->writeToRemote($data);
            });
    }

    public function writeToRemote($data)
    {
        $this->remoteSocket->write($data);
    }

    public function handleRemoteData($data)
    {
        file_put_contents('output.log', $data, FILE_APPEND);
        file_put_contents('output.log', "\r\n===================\r\n", FILE_APPEND);


        $this->clientSocket->write($data);
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
                    $address = substr($data, 1, 4);
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
                break;
        }

        if (!$address) {
            return false;
        }

        return [$addressType, $address, unpack('n*', $port)[1], $headerLength];
    }

    /**
     * 解析dns todo 处理未解析成功的域名
     *
     * @param $addressType
     * @param $host
     * @return null|Promise\FulfilledPromise|Promise\Promise
     */
    protected function resolveHostname($addressType, $host)
    {
        if (self::ADDRTYPE_HOST === ($addressType & self::ADDRTYPE_MASK)) {
            return $this->dns->resolve($host);
        }

        return Promise\resolve($host);
    }

    /**
     * todo 处理连接失败
     *
     * @param $address
     * @param $port
     * @return mixed
     */
    protected function createSocketForAddress($address, $port)
    {
        return $this->connector->connect($this->getSocketUrl($address, $port));
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