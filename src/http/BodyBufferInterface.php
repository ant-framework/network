<?php
namespace Ant\Network\Http;


use Evenement\EventEmitterInterface;

interface BodyBufferInterface extends EventEmitterInterface
{
    public function feed($data);
}