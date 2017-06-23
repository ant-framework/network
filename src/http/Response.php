<?php
namespace Ant\Network\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

/**
 * Todo 完善Response
 * Todo 是否保持对象不变性
 * Todo 两种Body,一种Body实现ReadableInterface,支持流式写入,另一种为传统Body,需要完整的Body进行响应
 *
 * Class Response
 * @package Ant\Network\Http
 */
class Response extends \Ant\Http\Response
{
    /**
     * @var WritableStreamInterface
     */
    protected $output;

    /**
     * 是否关闭
     *
     * @var bool
     */
    protected $closed = false;

    /**
     * 是否允许写入,在结束后不允许写入
     *
     * @var bool
     */
    protected $writable = true;

    /**
     * 头部是否写入
     *
     * @var bool
     */
    protected $headWritten = false;

    /**
     * 是否启用分块传输
     *
     * @var bool
     */
    protected $chunkedEncoding = false;

    /**
     * 不保持不变性
     *
     * @var bool
     */
    protected $immutability = false;

    /**
     * @var bool
     */
    protected $keepAlive = false;

    /**
     * @var array
     */
    protected static $defaultHeaders = [
        'X-Powered-By'      =>  'Ant-Framework',
        'Server'            =>  'ReactPhp/alpha',
    ];

    /**
     * @param RequestInterface $request
     * @param array $headers
     * @return static
     */
    public static function prepare(
        WritableStreamInterface $output,
        RequestInterface $request,
        array $headers = []
    ) {
        $connection = $request->getHeaderLine('Connection');

        if (strtolower($connection) == 'keep-alive') {
            $headers['Connection'] = ['keep-alive'];
        } else {
            $headers['Connection'] = ['close'];
        }

        if ($request->getMethod() === "HEAD") {
            // Todo HEAD头将不再响应Body
        }

        return new static($output, 200, $headers, null, '', $request->getProtocolVersion());
    }

    /**
     * @param int $code
     * @param array $headers
     * @param StreamInterface|null $body
     * @param string $phrase
     * @param string $protocol
     */
    public function __construct(
        WritableStreamInterface $output,
        $code = 200,
        $headers = [],
        StreamInterface $body = null,
        $phrase = '',
        $protocol = '1.1'
    ) {
        $this->output = $output;

        $headers = array_merge(static::$defaultHeaders, $headers);

        parent::__construct($code, $headers, $body, $phrase, $protocol);
    }

    public function headerToString()
    {
        if (!$this->hasHeader('date')) {
            $this->headers['Date'] = [gmdate('D, d M Y H:i:s') . ' GMT'];
        }

        if (!$this->hasHeader("content-length")) {
            $this->headers['Transfer-Encoding'] = ['chunked'];
            $this->chunkedEncoding = true;
        }

        if (strtolower($this->getHeaderLine('Connection')) == 'keep-alive') {
            $this->keepAlive = true;
        }

        return parent::headerToString();
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function writeContinue()
    {
        if (!$this->writable) {
            return;
        }
        if ($this->protocolVersion !== '1.1') {
            throw new \Exception('Continue requires a HTTP/1.1 message');
        }
        if ($this->headWritten) {
            throw new \Exception('Response head has already been written.');
        }

        $this->output->write("HTTP/1.1 100 Continue\r\n\r\n");
    }

    public function writeHead()
    {
        if (!$this->writable) {
            return;
        }
        if ($this->headWritten) {
            throw new \Exception('Response head has already been written.');
        }

        $data = $this->headerToString();

        $this->output->write("{$data}\r\n");

        $this->headWritten = true;
    }


    public function write($data)
    {
        if (!$this->writable) {
            return false;
        }
        if (!$this->headWritten) {
            $this->writeHead();
        }

        // prefix with chunk length for chunked transfer encoding
        if ($this->chunkedEncoding) {
            $len = strlen($data);

            // skip empty chunks
            if ($len === 0) {
                return true;
            }

            $data = dechex($len) . "\r\n" . $data . "\r\n";
        }

        return $this->output->write($data);
    }

    public function end($data = null)
    {
        if (!$this->writable) {
            return;
        }

        if (null !== $data) {
            $this->write($data);
        }

        if ($this->chunkedEncoding) {
            $this->output->write("0\r\n\r\n");
        }

        $this->writable = false;
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->writable = false;
        $this->output->close();
    }
}