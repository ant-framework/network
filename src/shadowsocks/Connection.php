<?php
namespace Ant\Network\Shadowsocks;

use React\Stream\Util;
use Evenement\EventEmitter;
use React\EventLoop\Timer\Timer;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

/**
 * Class Connection
 * @package Ant\Network\Socks
 */
class Connection extends EventEmitter implements ConnectionInterface
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var StreamEncryption
     */
    protected $cryptor;

    /**
     * @var ConnectionInterface
     */
    private $conn;

    /**
     * @var Timer
     */
    private $timer;

    /**
     * @var integer
     */
    private $keepAliveTime;

    /**
     * Connection constructor.
     * @param ConnectionInterface $conn
     * @param LoopInterface $loop
     * @param array $options
     */
    public function __construct(ConnectionInterface $conn, LoopInterface $loop, array $options)
    {
        $this->conn = $conn;
        $this->loop = $loop;

        $this->cryptor = new StreamEncryption($options['password'], $options['method']);

        Util::forwardEvents($conn, $this, ['end', 'error', 'close', 'pipe', 'drain']);

        $this->conn->on('close', [$this, 'close']);
        $this->conn->on('data', function ($chunk) {
            $this->emit('data', [$this->cryptor->decrypt($chunk), $this]);
        });
    }

    /**
     * @param $msecs
     * @param callable|null $callback
     */
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
        if (!$this->isWritable()) {
             return false;
        }

        $data = $this->cryptor->encrypt($data);

        return $this->conn->write($data);
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

        if ($this->cryptor) {
            $this->cryptor = null;
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