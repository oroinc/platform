<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfFile;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Utils;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFile;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

final class PdfFileTest extends TestCase
{
    use TempDirExtension;

    public function testGetStreamWithStreamInstance(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $pdfFile = new PdfFile($stream, 'application/pdf');

        self::assertSame($stream, $pdfFile->getStream());
    }

    public function testGetStreamWithFilePath(): void
    {
        $filePath = $this->getTempFile('pdf_file', 'test', '.pdf');
        $pdfFile = new PdfFile($filePath, 'application/pdf');

        self::assertInstanceOf(LazyOpenStream::class, $pdfFile->getStream());
    }

    public function testGetPathWithFilePath(): void
    {
        $filePath = $this->getTempFile('pdf_file', 'test', '.pdf');
        $pdfFile = new PdfFile($filePath, 'application/pdf');

        self::assertSame($filePath, $pdfFile->getPath());
    }

    public function testGetPathWithStreamCreatesTempFile(): void
    {
        $stream = Utils::streamFor('Sample PDF content');
        $pdfFile = new PdfFile($stream, 'application/pdf');

        $path = $pdfFile->getPath();
        self::assertFileExists($path);

        self::assertEquals($path, $pdfFile->getPath());

        $pdfFile = null;
        self::assertFileDoesNotExist($path);
    }

    public function testGetMimeType(): void
    {
        $mimeType = 'application/pdf';
        $pdfFile = new PdfFile($this->createMock(StreamInterface::class), $mimeType);

        self::assertSame($mimeType, $pdfFile->getMimeType());
    }
}
