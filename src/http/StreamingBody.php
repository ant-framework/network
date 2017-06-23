<?php
namespace Ant\Network\Http;


use Psr\Http\Message\StreamInterface;
use React\Stream\DuplexStreamInterface;

class StreamingBody implements StreamInterface, DuplexStreamInterface
{
}