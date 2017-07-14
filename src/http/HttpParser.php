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
 * Todo ����ʱ
 * Todo �ع�������
 * Todo ֧�ֶ����,�����ظ�����(����ȡ��)
 *
 * request������,����ȡ���ʱ,ͳһ��������
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
     * �ر����󻺳���
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
                // ����body����������
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
                // ��������쳣
                throw new HttpException(
                    431, "Maximum header size of {$this->maxHeaderSize} exceeded."
                );
            }

            // headerȫ���ִ�
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
        // ���ڽ�������
        $this->input->removeListener('data', [$this, 'handleData']);
        $this->emit('error', [$exception, $this]);
        $this->clear();
    }

    /**
     * �������������
     */
    public function clear()
    {
        $this->buffer = '';
        $this->bodyReceiver = null;
        $this->headerArrive = false;
    }

    /**
     * ��ΪHttpЭ���ǰ�˫��Э��,����header�ִ�֮��
     * �����������header�������ڵ�����,ͬʱ������������д��body������
     * ��body��ȫ�ִ�֮��,��Ϊhttp���󵽴�,��ʱ����Ӧ֮ǰ,��Ӧ�������µ�����
     * ���Ժ��������뽫�ᱻ����,ֱ����Ӧ���֮��,�ٿ�ʼ�����µ�����
     * �������Ϊheaders��body,ͬʱ��Body����Body���������д���
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
            // Todo ����Body������ȫ����,ֱ�ӶϿ�����
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
     * ��ȡServer��Ϣ
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
        // �ֿ����
        if ($request->hasHeader('Transfer-Encoding')) {
            if (strtolower($request->getHeaderLine('Transfer-Encoding')) !== 'chunked') {
                throw new HttpException(511, 'Only chunked-encoding is allowed for Transfer-Encoding');
            }

            return new ChunkedDecoderBuffer($request->getBody());
        }

        // ��֪����
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