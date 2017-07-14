<?php
namespace Ant\Network\Http;

use React\EventLoop\Timer\Timer;
use React\Stream\Util;
use Evenement\EventEmitter;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

class Connection extends EventEmitter implements ConnectionInterface
{
    protected $timeout = null;

    protected $conn = null;

    protected $timer = null;

    public function __construct(ConnectionInterface $conn, $timeout = 5)
    {
        $this->conn = $conn;

        $this->timeout = $timeout;

        Util::forwardEvents($conn, $this, ['end', 'error', 'close', 'pipe', 'drain']);

        $this->conn->on('data', [$this, 'handleData']);

        $this->setKeepAliveTime();
    }

    public function handleData($data)
    {
        \Ant\Coroutine\cancelTimer($this->timer);

        $this->setKeepAliveTime();

        $this->emit('data', [$data]);
    }

    protected function setKeepAliveTime()
    {
        $this->timer = \Ant\Coroutine\addTimer($this->timeout, function () {
            $this->emit('timeout', [$this]);
        });
    }

    public function setTimeout($msecs, callable $callback = null)
    {
        if ($callback) {
            $this->on('timeout', $callback);
        }

        $this->timeout = $msecs;
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