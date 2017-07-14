<?php
namespace Ant\Network\Http;

use Ant\Http\CliServerRequest;
use React\Stream\Util;
use Evenement\EventEmitter;
use Ant\Http\ServerRequest;
use React\Http\ChunkedDecoder;
use React\Http\LengthLimitedStream;
use Ant\Http\Exception\HttpException;
use React\Http\CloseProtectionStream;
use React\Socket\ConnectionInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * Todo 请求超时
 * Todo 重构解析器
 * Todo 支持对象池,允许重复利用(考虑取舍)
 *
 * request缓冲区,当读取完成时,统一返回数据
 *
 * Class RequestHeaderParser
 * @package Ant\Network\Http
 */
class HttpParser extends EventEmitter
{
    /**
     * @var ConnectionInterface
     */
    protected $input;

    /**
     * @var string
     */
    protected $buffer = '';

    /**
     * @var bool
     */
    protected $headerArrive = false;

    /**
     * @var bool
     */
    protected $closed = false;

    /**
     * @var BodyBufferInterface
     */
    protected $bodyReceiver = null;

    /**
     * @var int
     */
    protected $maxHeaderSize;

    /**
     * @var int
     */
    protected $maxBodySize;

    /**
     * @param ConnectionInterface $input
     * @param int $maxHeaderSize
     * @param int $maxBodySize
     */
    public function __construct(
        ConnectionInterface $input,
        $maxHeaderSize = 4096,
        $maxBodySize = 65535
    ) {
        $this->input = $input;
        $this->maxHeaderSize = $maxHeaderSize;
        $this->maxBodySize = $maxBodySize;

        $this->input->on('data', [$this, 'handleData']);
        $this->input->on('error', [$this, 'handleError']);
        $this->input->on('close', [$this, 'handleClose']);
    }

    /**
     * 关闭请求缓冲区
     */
    public function handleClose()
    {
        if ($this->closed) {
            return;
        }

        $this->clear();
        $this->closed = true;
        $this->input->close();

        $this->emit('close');
        $this->removeAllListeners();
    }

    /**
     * @param $data
     */
    public function handleData($data)
    {
        try {
            if ($this->headerArrive) {
                // 交给body接收器处理
                $this->bodyReceiver->feed($data);
                return;
            }

            $this->buffer .= $data;

            $endOfHeader = strpos($this->buffer, "\r\n\r\n");

            if (false !== $endOfHeader) {
                $currentHeaderSize = $endOfHeader;
            } else {
                $currentHeaderSize = strlen($this->buffer);
            }

            if ($currentHeaderSize > $this->maxHeaderSize) {
                // 数据溢出异常
                throw new HttpException(
                    431, "Maximum header size of {$this->maxHeaderSize} exceeded."
                );
            }

            // header全部抵达
            if (false !== $endOfHeader) {
                $this->headerArrive = true;
                $this->parseRequest();
            }
        } catch (\Exception $exception) {
            $this->handleError($exception);
        }
    }

    public function handleError($exception)
    {
        // 不在接收数据
        $this->input->removeListener('data', [$this, 'handleData']);
        $this->emit('error', [$exception, $this]);
        $this->clear();
    }

    /**
     * 清除缓冲区数据
     */
    public function clear()
    {
        $this->buffer = '';
        $this->bodyReceiver = null;
        $this->headerArrive = false;
    }

    /**
     * 因为Http协议是半双工协议,所以header抵达之后
     * 可以立刻清除header缓冲区内的数据,同时将后续的数据写入body缓冲区
     * 在body完全抵达之后,视为http请求到达,此时在响应之前,不应该受理新的请求
     * 所以后续的输入将会被抛弃,直到响应完成之后,再开始处理新的请求
     * 将请求分为headers与body,同时将Body交给Body接收器进行处理
     */
    protected function parseRequest()
    {
        list($headers, $body) = explode("\r\n\r\n", $this->buffer, 2);

        $this->buffer = '';

        $request = CliServerRequest::createFromString($headers, $this->createServerParams());

        $this->emit('header', [$request]);

        if ($this->checkSkipBody($request)) {
            $this->emit('data', [$request]);
            $this->clear();
            return;
        }

        $bodyReceiver = $this->createBodyReceiver($request);

        $bodyReceiver->on('complete', function () use ($request) {
            // Todo 超大Body将不完全接收,直接断开连接
            if ($request->getBody()->getSize() > $this->maxBodySize) {
                throw new HttpException(
                    413, "Maximum body size of {$this->maxBodySize} exceeded."
                );
            }

            $this->emit('data', [$request]);
            $this->clear();
        });

        $bodyReceiver->on('error', function ($error) {
            $this->emit('error', array($error));
        });

        $this->bodyReceiver = $bodyReceiver;

        $this->bodyReceiver->feed($body);
    }

    protected function checkSkipBody(ServerRequest $request)
    {
        return in_array($request->getOriginalMethod(), ['GET', 'HEAD', 'OPTIONS']) || $request->hasHeader('Upgrade');
    }

    /**
     * 获取Server信息
     *
     * @return array
     */
    protected function createServerParams()
    {
        $serverParams = [
            'REQUEST_TIME'          =>  time(),
            'REQUEST_TIME_FLOAT'    =>  microtime(true),
            'SERVER_SOFTWARE'       =>  'Ant-Network/alpha',
        ];

        $remoteAddressParts = parse_url($this->input->getRemoteAddress());
        $localAddressParts = parse_url($this->input->getLocalAddress());

        $serverParams['REMOTE_ADDR'] = $remoteAddressParts['host'];
        $serverParams['REMOTE_PORT'] = $remoteAddressParts['port'];
        $serverParams['SERVER_ADDR'] = $localAddressParts['host'];
        $serverParams['SERVER_PORT'] = $localAddressParts['port'];

        return $serverParams;
    }

    /**
     * @param ServerRequest $request
     * @return BodyBufferInterface
     * @throw HttpException
     */
    protected function createBodyReceiver(ServerRequest $request)
    {
        // 分块编码
        if ($request->hasHeader('Transfer-Encoding')) {
            if (strtolower($request->getHeaderLine('Transfer-Encoding')) !== 'chunked') {
                throw new HttpException(511, 'Only chunked-encoding is allowed for Transfer-Encoding');
            }

            return new ChunkedDecoderBuffer($request->getBody());
        }

        // 已知长度
        if ($request->hasHeader('Content-Length')) {
            $string = $request->getHeaderLine('Content-Length');

            $contentLength = (int)$string;

            if ((string)$contentLength !== (string)$string) {
                throw new HttpException(400, 'The value of `Content-Length` is not valid');
            }

            return new LengthLimitedBuffer($request->getBody(), $contentLength);
        }

        throw new HttpException(411, "Expectation failed");
    }
}