<?php
namespace Ant\Network\Http;


use Psr\Http\Message\StreamInterface;
use React\Stream\DuplexStreamInterface;
use React\Stream\WritableStreamInterface;

class StreamingBody implements StreamInterface, DuplexStreamInterface
{
    /**
     * @var WritableStreamInterface
     */
    protected $output;

    public function write($data)
    {
        $len = strlen($data);

        // skip empty chunks
        if ($len === 0) {
            return true;
        }

        $data = dechex($len) . "\r\n" . $data . "\r\n";

        $this->output->write($data);

        return $len;
    }

    public function end($data = null)
    {
        $this->output->write("0\r\n\r\n");
    }
}