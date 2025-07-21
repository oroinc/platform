<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfFile;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Represents a generated PDF file.
 */
class PdfFile implements PdfFileInterface
{
    private static ?Filesystem $filesystem = null;

    private ?string $tempFile = null;

    public function __construct(
        private StreamInterface|string $streamOrPath,
        private string $mimeType
    ) {
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
        if (is_string($this->streamOrPath)) {
            return $this->streamOrPath;
        }

        $this->tempFile ??= $this->createTempFile();

        return $this->tempFile;
    }

    #[\Override]
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    private function createTempFile(): string
    {
        $tempFile = $this->getFilesystem()->tempnam(sys_get_temp_dir(), 'pdf_file_', '.pdf');

        Utils::copyToStream(
            $this->getStream(),
            new LazyOpenStream($tempFile, 'w')
        );

        return $tempFile;
    }

    private function getFilesystem(): Filesystem
    {
        self::$filesystem ??= new Filesystem();

        return self::$filesystem;
    }

    public function __destruct()
    {
        if ($this->tempFile !== null) {
            try {
                $this->getFilesystem()->remove($this->tempFile);
            } catch (IOException $exception) {
                // If an exception occurs in __destruct(), it can lead to unhandled fatal errors in PHP,
                // as throwing exceptions inside a destructor is generally discouraged.
            }
        }
    }
}
