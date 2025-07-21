<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfFile\Factory;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\Factory\FileEntityFromPdfFileFactory;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

final class FileEntityFromPdfFileFactoryTest extends TestCase
{
    private FileManager&MockObject $fileManager;

    private FileEntityFromPdfFileFactory $factory;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->factory = new FileEntityFromPdfFileFactory($this->fileManager);
    }

    public function testCreateFile(): void
    {
        $pdfContent = 'PDF content';
        $filename = 'test.pdf';
        $mimeType = 'application/pdf';
        $tempFile = $this->createMock(ComponentFile::class);

        $pdfFile = $this->createMock(PdfFileInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $pdfFile
            ->expects(self::once())
            ->method('getStream')
            ->willReturn($stream);

        $stream
            ->expects(self::once())
            ->method('getContents')
            ->willReturn($pdfContent);

        $pdfFile
            ->expects(self::once())
            ->method('getMimeType')
            ->willReturn($mimeType);

        $this->fileManager
            ->expects(self::once())
            ->method('writeToTemporaryFile')
            ->with($pdfContent)
            ->willReturn($tempFile);

        $result = $this->factory->createFile($pdfFile, $filename);

        self::assertSame($tempFile, $result->getFile());
        self::assertSame($filename, $result->getOriginalFilename());
        self::assertSame($mimeType, $result->getMimeType());
    }
}
