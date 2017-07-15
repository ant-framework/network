<?php
namespace Ant\Network\Http;

use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;
use React\Stream\Util;
use Evenement\EventEmitter;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

/**
 * Class Connection
 * @package Ant\Network\Http
 */
class Connection extends EventEmitter implements ConnectionInterface
{
    protected $loop;

    private $conn;

    private $timer;

    private $keepAliveTime;

    public function __construct(ConnectionInterface $conn, LoopInterface $loop, $keepAliveTime = null)
    {
        $this->conn = $conn;
        $this->loop = $loop;
        $this->keepAliveTime = $keepAliveTime;

        // 事件绑定
        Util::forwardEvents($conn, $this, ['end', 'error', 'close', 'pipe', 'drain']);
        // 每次数据抵达的时候重置超时时间
        $this->conn->on('data', [$this, 'handleData']);
        // 设置保持连接时间,每次数据抵达刷新时间
        $this->setKeepAliveTime();
    }

    public function handleData($data)
    {
        $this->loop->cancelTimer($this->timer);

        $this->setKeepAliveTime();

        $this->emit('data', [$data]);
    }

    public function setTimeout($msecs, callable $callback = null)
    {
        if ($callback) {
            $this->on('timeout', $callback);
        }

        $this->keepAliveTime = $msecs;
    }

    protected function setKeepAliveTime()
    {
        // 永不超时
        if ($this->keepAliveTime === null) {
            return;
        }

        $this->timer = $this->loop->addTimer($this->keepAliveTime, function () {
            $this->emit('timeout', [$this]);
        });
    }

    public function isReadable()
    {
        return $this->conn->isReadable();
    }

    public function isWritable()
    {
        return $this->conn->isWritable();
    }

    public function pause()
    {
        $this->conn->pause();
    }

    public function resume()
    {
        $this->conn->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        $this->conn->pipe($dest, $options);
    }

    public function write($data)
    {
        $this->conn->write($data);
    }

    public function end($data = null)
    {
        $this->conn->end($data);
    }

    public function close()
    {
        $this->conn->close();
    }

    public function getRemoteAddress()
    {
        return $this->conn->getRemoteAddress();
    }

    public function getLocalAddress()
    {
        return $this->conn->getLocalAddress();
    }
}