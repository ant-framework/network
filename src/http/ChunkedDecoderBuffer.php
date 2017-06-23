<?php
namespace Ant\Network\Http;

use Evenement\EventEmitter;

/**
 * Todo 重构分块解码
 *
 * Class ChunkedDecoderBuffer
 * @package Ant\Network\Http
 */
class ChunkedDecoderBuffer extends EventEmitter implements BodyBufferInterface
{
    const CRLF = "\r\n";
    const MAX_CHUNK_HEADER_SIZE = 1024;

    private $buffer = '';
    private $chunkSize = 0;
    private $transferredSize = 0;
    private $headerCompleted = false;

    /** @internal */
    public function feed($data)
    {
        $this->buffer .= $data;

        while ($this->buffer !== '') {
            if (!$this->headerCompleted) {
                $positionCrlf = strpos($this->buffer, static::CRLF);

                if ($positionCrlf === false) {
                    // Header shouldn't be bigger than 1024 bytes
                    if (isset($this->buffer[static::MAX_CHUNK_HEADER_SIZE])) {
                        throw new \Exception('Chunk header size inclusive extension bigger than' . static::MAX_CHUNK_HEADER_SIZE. ' bytes');
                    }
                    return;
                }

                $header = strtolower((string)substr($this->buffer, 0, $positionCrlf));
                $hexValue = $header;

                if (strpos($header, ';') !== false) {
                    $array = explode(';', $header);
                    $hexValue = $array[0];
                }

                if ($hexValue !== '') {
                    $hexValue = ltrim($hexValue, "0");
                    if ($hexValue === '') {
                        $hexValue = "0";
                    }
                }

                $this->chunkSize = hexdec($hexValue);
                if (dechex($this->chunkSize) !== $hexValue) {
                    throw new \Exception($hexValue . ' is not a valid hexadecimal number');
                }

                $this->buffer = (string)substr($this->buffer, $positionCrlf + 2);
                $this->headerCompleted = true;
                if ($this->buffer === '') {
                    return;
                }
            }

            $chunk = (string)substr($this->buffer, 0, $this->chunkSize - $this->transferredSize);

            if ($chunk !== '') {
                $this->transferredSize += strlen($chunk);
                $this->emit('data', array($chunk));
                $this->buffer = (string)substr($this->buffer, strlen($chunk));
            }

            $positionCrlf = strpos($this->buffer, static::CRLF);

            if ($positionCrlf === 0) {
                if ($this->chunkSize === 0) {
                    $this->emit('end');
                    return;
                }
                $this->chunkSize = 0;
                $this->headerCompleted = false;
                $this->transferredSize = 0;
                // 去掉行末换行符 \r\n
                $this->buffer = (string)substr($this->buffer, 2);
            }

            if ($positionCrlf !== 0 && $this->chunkSize === $this->transferredSize && strlen($this->buffer) > 2) {
                // the first 2 characters are not CLRF, send error event
                throw new \Exception('Chunk does not end with a CLRF');
            }

            if ($positionCrlf !== 0 && strlen($this->buffer) < 2) {
                // No CLRF found, wait for additional data which could be a CLRF
                return;
            }
        }
    }
}