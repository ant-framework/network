<?php
namespace Ant\Network\Http;

use Ant\Http\Exception\HttpException;
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
        // Todo Body实现流式读取
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
            $this->emit('complete');
        }
    }
}