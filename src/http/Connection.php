<?php
namespace Ant\Network\Http;

use React\Stream\Util;
use Evenement\EventEmitter;
use React\EventLoop\Timer\Timer;
use React\EventLoop\LoopInterface;
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

    public function __construct(ConnectionInterface $conn, LoopInterface $loop)
    {
        $this->conn = $conn;
        $this->loop = $loop;

        Util::forwardEvents($conn, $this, ['end', 'error', 'close', 'pipe', 'drain']);
        $this->conn->on('data', [$this, 'handleData']);
    }

    public function handleData($data)
    {
        $this->setKeepAliveTime();

        $this->emit('data', [$data]);
    }

    public function setTimeout($msecs, callable $callback = null)
    {
        if ($callback) {
            $this->on('timeout', $callback);
        }

        $this->keepAliveTime = $msecs;

        $this->setKeepAliveTime();
    }

    /**
     * 设置保持长连接的时间
     */
    protected function setKeepAliveTime()
    {
        if ($this->timer) {
            $this->loop->cancelTimer($this->timer);
        }

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
        $this->removeAllListeners();

        if ($this->timer) {
            $this->loop->cancelTimer($this->timer);
        }

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