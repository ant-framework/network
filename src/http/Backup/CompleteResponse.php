<?php
namespace Ant\Network\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

/**
 * Todo 完善Response
 * Todo 是否保持对象不变性
 *
 * Class Response
 * @package Ant\Network\Http
 * @property StreamInterface body
 * @property WritableStreamInterface socket
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

        if (!$this->isEmpty() && !$this->hasHeader("content-length")) {
            $this->headers['Content-Length'] = $this->getBody()->getSize();
        }

        if (strtolower($this->getHeaderLine('Connection')) == 'keep-alive') {
            $this->keepAlive = true;
        }

        return parent::headerToString();
    }

    public function __get($name)
    {
        if ($name == 'body') {
            return $this->getBody();
        }

        if ($name == 'socket') {
            return $this->output;
        }

        throw new \RuntimeException;
    }

    /**
     * 响应结束
     *
     * @throws \Exception
     */
    public function end()
    {
        // Todo 修复响应后断开连接
        // Todo 每次客户端会建立新连接
        if (!$this->writable) {
            return;
        }

        $this->writable = false;

        $this->output->write((string) $this);
    }
}