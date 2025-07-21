<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfTemplateAsset;

use Oro\Bundle\PdfGeneratorBundle\Exception\PdfTemplateAssetException;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetFactory;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

final class PdfTemplateAssetFactoryTest extends TestCase
{
    private PdfTemplateAssetFactoryInterface&MockObject $applicableFactory;

    private PdfTemplateAssetInterface&MockObject $pdfTemplateAsset;

    private PdfTemplateAssetFactory $factory;

    protected function setUp(): void
    {
        $this->applicableFactory = $this->createMock(PdfTemplateAssetFactoryInterface::class);
        $this->pdfTemplateAsset = $this->createMock(PdfTemplateAssetInterface::class);

        $this->factory = new PdfTemplateAssetFactory([$this->applicableFactory]);
    }

    public function testCreateFromPathWithApplicableFactory(): void
    {
        $filepath = '/tmp/sample.pdf';
        $name = 'sample.pdf';

        $this->applicableFactory
            ->expects(self::once())
            ->method('isApplicable')
            ->with($name, $filepath, null, [])
            ->willReturn(true);

        $this->applicableFactory
            ->expects(self::once())
            ->method('createFromPath')
            ->with($filepath, $name, [])
            ->willReturn($this->pdfTemplateAsset);

        $result = $this->factory->createFromPath($filepath, $name);

        self::assertSame($this->pdfTemplateAsset, $result);
    }

    public function testCreateFromPathWithNoApplicableFactory(): void
    {
        $this->expectExceptionObject(
            new PdfTemplateAssetException('Failed to create a PDF template asset: no applicable factory found.')
        );

        $this->factory->createFromPath('test.pdf');
    }

    public function testCreateFromRawDataWithApplicableFactory(): void
    {
        $data = 'sample pdf content';
        $name = 'sample.pdf';

        $this->applicableFactory->expects(self::once())
            ->method('isApplicable')
            ->with($name, null, null, [])
            ->willReturn(true);

        $this->applicableFactory->expects(self::once())
            ->method('createFromRawData')
            ->with($data, $name, [])
            ->willReturn($this->pdfTemplateAsset);

        $result = $this->factory->createFromRawData($data, $name);

        self::assertSame($this->pdfTemplateAsset, $result);
    }

    public function testCreateFromRawDataWithNoApplicableFactory(): void
    {
        $this->expectExceptionObject(
            new PdfTemplateAssetException('Failed to create a PDF template asset: no applicable factory found.')
        );

        $this->factory->createFromRawData('sample pdf content', 'test.pdf');
    }

    public function testCreateFromStreamWithApplicableFactory(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $name = 'sample.pdf';

        $this->applicableFactory->expects(self::once())
            ->method('isApplicable')
            ->with($name, null, null, [])
            ->willReturn(true);

        $this->applicableFactory->expects(self::once())
            ->method('createFromStream')
            ->with($stream, $name, [])
            ->willReturn($this->pdfTemplateAsset);

        $result = $this->factory->createFromStream($stream, $name);

        self::assertSame($this->pdfTemplateAsset, $result);
    }

    public function testCreateFromStreamWithNoApplicableFactory(): void
    {
        $this->expectExceptionObject(
            new PdfTemplateAssetException('Failed to create a PDF template asset: no applicable factory found.')
        );

        $this->factory->createFromStream($this->createMock(StreamInterface::class), 'test.pdf');
    }

    public function testIsApplicableReturnsTrueWhenAnyInnerFactoryIsApplicable(): void
    {
        $this->applicableFactory->expects(self::once())
            ->method('isApplicable')
            ->willReturn(true);

        self::assertTrue($this->factory->isApplicable('sample.pdf', null, null, []));
    }

    public function testIsApplicableReturnsFalseWhenNoInnerFactoryIsApplicable(): void
    {
        $this->applicableFactory->expects(self::once())
            ->method('isApplicable')
            ->willReturn(false);

        self::assertFalse($this->factory->isApplicable('sample.pdf', null, null, []));
    }
}
