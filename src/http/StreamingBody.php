<?php
namespace Ant\Network\Http;


use Evenement\EventEmitterTrait;
use Psr\Http\Message\StreamInterface;
use React\Stream\WritableStreamInterface;

class StreamingBody implements StreamInterface, WritableStreamInterface
{
    use EventEmitterTrait;

    /**
     * @var WritableStreamInterface
     */
    protected $output;

    protected $size = 0;

    protected $writable = true;

    protected $headWritten = false;

    /**
     * @param WritableStreamInterface $output
     * @param $headers
     */
    public function __construct(WritableStreamInterface $output, $headers)
    {
        $this->output = $output;
        $this->headers = $headers;
    }

    public function __toString()
    {
        return "";
    }

    public function close()
    {
        $this->writable = false;
    }

    public function detach()
    {
        return null;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function tell()
    {
        throw new \BadMethodCallException();
    }

    public function eof()
    {
        throw new \BadMethodCallException();
    }

    public function isSeekable()
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \BadMethodCallException();
    }

    public function rewind()
    {
        throw new \BadMethodCallException();
    }

    public function isReadable()
    {
        return false;
    }

    public function read($length)
    {
        throw new \BadMethodCallException();
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function write($data)
    {
        if (!$this->writable) {
            return false;
        }

        if (!$this->headWritten) {
            $this->output->write($this->headers . "\r\n");
            $this->headWritten = true;
            unset($this->headers);
        }

        $len = strlen($data);

        $this->size += $len;

        // skip empty chunks
        if ($len === 0) {
            return true;
        }

        $data = dechex($len) . "\r\n" . $data . "\r\n";

        $this->output->write($data);

        return $len;
    }

    public function getContents()
    {
        return null;
    }

    public function getMetadata($key = null)
    {
        return null;
    }

    public function end($data = null)
    {
        if (!$this->writable) {
            return;
        }

        $this->output->write("0\r\n\r\n");

        $this->writable = false;
    }
}