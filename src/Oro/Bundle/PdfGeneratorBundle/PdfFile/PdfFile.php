<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfFile;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

/**
 * Represents a generated PDF file.
 */
class PdfFile implements PdfFileInterface
{
    /** @var resource|false|null */
    private $tempFileHandle = null;
    private ?string $tempFilePath = null;

    public function __construct(
        private StreamInterface|string $streamOrPath,
        private string $mimeType
    ) {
    }

    public function __destruct()
    {
        if ($this->tempFileHandle && \is_resource($this->tempFileHandle)) {
            @fclose($this->tempFileHandle);
        }
    }

    #[\Override]
    public function getStream(): StreamInterface
    {
        if ($this->streamOrPath instanceof StreamInterface) {
            return $this->streamOrPath;
        }

        return new LazyOpenStream($this->streamOrPath, 'r');
    }

    #[\Override]
    public function getPath(): string
    {
        if (\is_string($this->streamOrPath)) {
            return $this->streamOrPath;
        }

        if (null === $this->tempFileHandle) {
            $this->tempFileHandle = tmpfile();
            if (!$this->tempFileHandle) {
                throw new \RuntimeException('Cannot create a temporary file to store PDF document.');
            }
            $this->tempFilePath = stream_get_meta_data($this->tempFileHandle)['uri'];
            Utils::copyToStream($this->getStream(), new LazyOpenStream($this->tempFilePath, 'w'));
        }
        if (null === $this->tempFilePath) {
            throw new \RuntimeException('A temporary file to store PDF document does not exist.');
        }

        return $this->tempFilePath;
    }

    #[\Override]
    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}
