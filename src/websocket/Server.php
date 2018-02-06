<?php
namespace Ant\Network\WebSocket;


use Evenement\EventEmitter;
use Psr\Http\Message\RequestInterface;
use React\Socket\ConnectionInterface;

class Server extends EventEmitter
{
    public function __construct(\Ant\Network\Http\Server $sever)
    {
        $sever->on('upgrade', [$this, 'handleConnection']);
    }

    // Todo 将Connection I/O包裹起来
    public function handleConnection(RequestInterface $req, ConnectionInterface $conn)
    {
        $this->emit('connection', [$conn]);
    }
}