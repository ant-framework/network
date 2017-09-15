<?php
namespace Ant\Network\Socks;

use React\Stream\Util;
use Evenement\EventEmitter;
use React\EventLoop\Timer\Timer;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

class Connection extends EventEmitter implements ConnectionInterface
{
    protected $loop;

    protected $parser;

    protected $method;

    protected $version;

    private $conn;

    private $timer;

    private $keepAliveTime;

    /**
     * Connection constructor.
     * @param ConnectionInterface $conn
     * @param LoopInterface $loop
     */
    public function __construct(ConnectionInterface $conn, LoopInterface $loop)
    {
        $this->stage = Stage::INIT;
        $this->conn = $conn;
        $this->loop = $loop;
        $this->parser = new Parser();

        Util::forwardEvents($conn, $this, ['end', 'error', 'close', 'pipe', 'drain']);
        $this->conn->on('data', [$this, 'handleData']);
    }

    public function handleData($data)
    {
        $this->setKeepAliveTime();

        switch ($this->stage) {
            case Stage::INIT :
                list($this->version, $this->method) = $this->parser->parseSocksHeader($data);
                break;
            case Stage::AUTH :
                if ($this->getAuthMethod() === 0x02) {
                    list($this->username, $this->password) = $this->parser->parseUsernameAndPassword($data);
                }
                break;
        }

        $this->emit($this->stage, [$this, $data]);
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getAuthMethod()
    {
        return $this->method;
    }

    public function setStage($stage)
    {
        if (!Stage::isStage($stage)) {
            // Todo Exception
        }

        $this->stage = $stage;
    }

    public function getStage()
    {
        return $this->stage;
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