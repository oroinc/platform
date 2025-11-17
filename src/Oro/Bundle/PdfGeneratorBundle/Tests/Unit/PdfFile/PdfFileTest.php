<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfFile;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Utils;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFile;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

final class PdfFileTest extends TestCase
{
    use TempDirExtension;

    private StreamInterface&MockObject $stream;
    private string $filePath;
    private string $mimeType;

    protected function setUp(): void
    {
        $this->stream = $this->createMock(StreamInterface::class);
        $this->filePath = $this->getTempFile('pdf_file', 'test', '.pdf');
        $this->mimeType = 'application/pdf';
    }

    public function testGetStreamWithStreamInstance(): void
    {
        $pdfFile = new PdfFile($this->stream, $this->mimeType);

        self::assertSame($this->stream, $pdfFile->getStream());
    }

    public function testGetStreamWithFilePath(): void
    {
        $pdfFile = new PdfFile($this->filePath, $this->mimeType);

        self::assertInstanceOf(LazyOpenStream::class, $pdfFile->getStream());
    }

    public function testGetPathWithFilePath(): void
    {
        $pdfFile = new PdfFile($this->filePath, $this->mimeType);

        self::assertSame($this->filePath, $pdfFile->getPath());
    }

    public function testGetPathWithStreamCreatesTempFile(): void
    {
        $stream = Utils::streamFor('Sample PDF content');
        $pdfFile = new PdfFile($stream, $this->mimeType);

        $tempFile = $pdfFile->getPath();
        self::assertFileExists($tempFile);

        unset($pdfFile);
        self::assertFileDoesNotExist($tempFile);
    }

    public function testGetMimeType(): void
    {
        $pdfFile = new PdfFile($this->stream, $this->mimeType);

        self::assertSame($this->mimeType, $pdfFile->getMimeType());
    }
}
