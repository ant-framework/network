<?php
namespace Ant\Network\Http;

use Evenement\EventEmitter;
use Psr\Http\Message\StreamInterface;

/**
 * Class LengthLimitedBuffer
 * @package Ant\Network\Http
 */
class LengthLimitedBuffer extends EventEmitter implements BodyBufferInterface
{
    protected $body;
    protected $maxLength;

    public function __construct(StreamInterface $body, $maxLength)
    {
        $this->body = $body;
        $this->maxLength = $maxLength;
    }

    public function feed($data)
    {
        if ($data !== '') {
            $this->body->write($data);
        }

        if ($this->body->getSize() === $this->maxLength) {
            // 'Content-Length' reached, stream will end
            $this->emit('end');
        }
    }
}