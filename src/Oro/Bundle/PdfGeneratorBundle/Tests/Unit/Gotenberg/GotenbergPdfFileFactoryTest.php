<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Gotenberg;

use Oro\Bundle\PdfGeneratorBundle\Gotenberg\GotenbergPdfFileFactory;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFile;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class GotenbergPdfFileFactoryTest extends TestCase
{
    private GotenbergPdfFileFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new GotenbergPdfFileFactory();
    }

    public function testCreatePdfFileWithContentType(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response
            ->method('getBody')
            ->willReturn($stream);
        $response
            ->method('getHeader')
            ->with('Content-Type')
            ->willReturn(['application/pdf']);

        $pdfFile = $this->factory->createPdfFile($response);

        self::assertInstanceOf(PdfFile::class, $pdfFile);
        self::assertSame($stream, $pdfFile->getStream());
        self::assertSame('application/pdf', $pdfFile->getMimeType());
    }

    public function testCreatePdfFileWithoutContentType(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response
            ->method('getBody')
            ->willReturn($stream);
        $response
            ->method('getHeader')
            ->with('Content-Type')
            ->willReturn([]);

        $pdfFile = $this->factory->createPdfFile($response);

        self::assertInstanceOf(PdfFile::class, $pdfFile);
        self::assertSame($stream, $pdfFile->getStream());
        self::assertEquals('application/pdf', $pdfFile->getMimeType());
    }

    public function testCreatePdfFileWithEmptyContentType(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response
            ->method('getBody')
            ->willReturn($stream);
        $response
            ->method('getHeader')
            ->with('Content-Type')
            ->willReturn(['']);

        $pdfFile = $this->factory->createPdfFile($response);

        self::assertInstanceOf(PdfFile::class, $pdfFile);
        self::assertSame($stream, $pdfFile->getStream());
        self::assertEquals('application/pdf', $pdfFile->getMimeType());
    }
}
