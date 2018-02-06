<?php
namespace Ant\Network\Socks;

use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;
use Evenement\EventEmitterInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Server as TcpServer;

class Server implements EventEmitterInterface
{
    use EventEmitterTrait;

    protected $loop;

    protected $options = [];

    /**
     * @param LoopInterface $loop
     * @param array $options
     */
    public function __construct(LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;

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
        $socket = new Connection($connection, $this->loop);

        $this->emit('connection', [$socket]);

        if (!$socket->listeners(Stage::INIT)) {
            $socket->on(Stage::INIT, [$this, 'onStageInit']);
        }

        if (!$socket->listeners(Stage::AUTH)) {
            $socket->on(Stage::AUTH, [$this, 'onStageAuth']);
        }

        if (!$socket->listeners(Stage::RUNNING)) {
            $socket->on(Stage::RUNNING, [$this, 'onStageRunning']);
        }
    }

    public function onStageInit(Connection $socket)
    {
        if ($socket->getVersion() !== 0x05) {
            // 仅支持socks5
            $socket->end("\x05\xff");
            return;
        }

        $method = $socket->getAuthMethod();

        switch ($method) {
            case 0x00 :
                // 跳过验证
                $socket->setStage(Stage::RUNNING);
                break;
            case 0x02 ;
                // 验证用户名
                $socket->setStage(Stage::AUTH);
                break;
            default :
                $socket->end("\x05\xff");
                return;
        }

        $socket->write("\x05" . chr($method));
    }

    public function onStageAuth(Connection $socket, $data)
    {
        if ($socket->getAuthMethod() === 0x02) {
        }
    }

    public function onStageRunning(Connection $socket, $data)
    {
    }
}