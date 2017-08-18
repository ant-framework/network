<?php
namespace Ant\Network\Http;

use Evenement\EventEmitterTrait;
use Evenement\EventEmitterInterface;
use Ant\Http\Response as PsrResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

/**
 * Todo 是否保持对象不变性
 *
 * Class Response
 * @package Ant\Network\Http
 */
class Response extends PsrResponse implements EventEmitterInterface
{
    use EventEmitterTrait;

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
    protected $isEnd = false;

    /**
     * @var bool
     */
    protected $keepAlive = false;

    /**
     * @var array
     */
    protected static $defaultHeaders = [
        'X-Powered-By'      =>  'Ant-Framework',
        'Server'            =>  'ant-network/alpha',
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

    /**
     * @return string
     */
    public function headerToString()
    {
        if (!$this->hasHeader('date')) {
            $this->headers['date'] = [gmdate('D, d M Y H:i:s') . ' GMT'];
        }

        if (strtolower($this->getHeaderLine('Connection')) == 'keep-alive') {
            $this->keepAlive = true;
        }

        return parent::headerToString();
    }

    /**
     * @return StreamingBody
     */
    public function bodyConvertToStream()
    {
        if ($this->body instanceof StreamingBody) {
            return $this;
        }

        $oldBody = $this->body;

        $this->headers['transfer-encoding'] = ['chunked'];

        // Todo 不在允许写入Header
        $body = new StreamingBody($this->output, $this->headerToString());

        $new = $this->withBody($body);

        $new->getBody()->write((string) $oldBody);

        return $new;
    }

    /**
     * @param null $data
     */
    public function end($data = null)
    {
        if ($this->isEnd) {
            return;
        }

        if (null !== $data) {
            $this->getBody()->write($data);
        }

        if ($this->body instanceof StreamingBody) {
            $this->body->end();
        } else {
            $this->output->write((string) $this);
        }

        if (!$this->keepAlive) {
            $this->output->end();
        }

        $this->isEnd = true;
    }
}