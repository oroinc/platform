<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfTemplateAsset;

use GuzzleHttp\Psr7\Utils;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfTemplateAssetException;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAsset;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetCssFactory;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PdfTemplateAssetCssFactoryTest extends TestCase
{
    private PdfTemplateAssetFactoryInterface&MockObject $basicPdfTemplateAssetFactory;

    private PdfTemplateAssetFactoryInterface&MockObject $pdfTemplateAssetFactory;

    private PdfTemplateAssetCssFactory $factory;

    protected function setUp(): void
    {
        $this->basicPdfTemplateAssetFactory = $this->createMock(PdfTemplateAssetFactoryInterface::class);
        $this->pdfTemplateAssetFactory = $this->createMock(PdfTemplateAssetFactoryInterface::class);

        $this->factory = new PdfTemplateAssetCssFactory(
            $this->basicPdfTemplateAssetFactory,
            $this->pdfTemplateAssetFactory
        );
    }

    public function testCreateFromPathWhenNoInnerAssets(): void
    {
        $assetName = 'style.css';
        $filepath = '/path/to/style.css';
        $cssContent = 'body { background: #cccccc; }';
        $stream = Utils::streamFor($cssContent);
        $pdfTemplateAsset = new PdfTemplateAsset($assetName, $filepath, $stream);

        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromPath')
            ->with($filepath)
            ->willReturn($pdfTemplateAsset);

        $result = $this->factory->createFromPath($filepath);
        self::assertSame($pdfTemplateAsset, $result);
    }

    public function testCreateFromPathExtractsInnerAssets(): void
    {
        $assetName = 'style.css';
        $filepath = '/path/to/style.css';
        $cssContent = "body { background: url('images/bg.png'); }";
        $stream = Utils::streamFor($cssContent);
        $pdfTemplateAsset = new PdfTemplateAsset($assetName, $filepath, $stream);

        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromPath')
            ->with($filepath)
            ->willReturn($pdfTemplateAsset);

        $innerAssetName = 'images__bg.png';
        $innerFilepath = '/path/to/images/bg.png';
        $innerPdfTemplateAsset = new PdfTemplateAsset(
            $innerAssetName,
            null,
            $this->createMock(StreamInterface::class)
        );

        $this->pdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromPath')
            ->with($innerFilepath, $innerAssetName)
            ->willReturn($innerPdfTemplateAsset);

        $newStream = $this->createMock(StreamInterface::class);
        $newPdfTemplateAsset = new PdfTemplateAsset(
            $assetName,
            $filepath,
            $newStream,
            [$innerPdfTemplateAsset->getName() => $innerPdfTemplateAsset]
        );
        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromStream')
            ->with(
                self::isInstanceOf(StreamInterface::class),
                $assetName,
                [$innerPdfTemplateAsset->getName() => $innerPdfTemplateAsset]
            )
            ->willReturn($newPdfTemplateAsset);

        $result = $this->factory->createFromPath($filepath);
        self::assertSame($newPdfTemplateAsset, $result);
    }

    public function testCreateFromPathExtractsInnerAssetsAndAddsExtraAssets(): void
    {
        $assetName = 'style.css';
        $filepath = '/path/to/style.css';
        $cssContent = "body { background: url('images/bg.png'); }";
        $stream = Utils::streamFor($cssContent);
        $pdfTemplateAsset2 = new PdfTemplateAsset('script.js', null, $this->createMock(StreamInterface::class));
        $pdfTemplateAsset = new PdfTemplateAsset(
            $assetName,
            $filepath,
            $stream,
            [$pdfTemplateAsset2->getName() => $pdfTemplateAsset2]
        );

        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromPath')
            ->with($filepath)
            ->willReturn($pdfTemplateAsset);

        $innerAssetName = 'images__bg.png';
        $innerFilepath = '/path/to/images/bg.png';
        $innerPdfTemplateAsset = new PdfTemplateAsset(
            $innerAssetName,
            null,
            $this->createMock(StreamInterface::class)
        );

        $this->pdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromPath')
            ->with($innerFilepath, $innerAssetName)
            ->willReturn($innerPdfTemplateAsset);

        $newStream = $this->createMock(StreamInterface::class);
        $newPdfTemplateAsset = new PdfTemplateAsset(
            $assetName,
            $filepath,
            $newStream,
            [
                $innerPdfTemplateAsset->getName() => $innerPdfTemplateAsset,
                $pdfTemplateAsset2->getName() => $pdfTemplateAsset2,
            ]
        );
        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromStream')
            ->with(
                self::isInstanceOf(StreamInterface::class),
                $assetName,
                [
                    $innerPdfTemplateAsset->getName() => $innerPdfTemplateAsset,
                    $pdfTemplateAsset2->getName() => $pdfTemplateAsset2,
                ]
            )
            ->willReturn($newPdfTemplateAsset);

        $result = $this->factory->createFromPath($filepath, null, [$pdfTemplateAsset2]);
        self::assertSame($newPdfTemplateAsset, $result);
    }

    public function testCreateFromPathThrowsExceptionOnUnreadableStream(): void
    {
        $assetName = 'style.css';
        $filepath = '/path/to/style.css';
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->expects(self::once())
            ->method('isReadable')
            ->willReturn(false);

        $pdfTemplateAsset = new PdfTemplateAsset($assetName, null, $stream);
        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromPath')
            ->willReturn($pdfTemplateAsset);

        $this->expectException(PdfTemplateAssetException::class);
        $this->expectExceptionMessage(
            'Impossible to extract inner assets from the non-readable/non-seekable PDF template asset "style.css"'
        );

        $this->factory->createFromPath($filepath);
    }

    public function testCreateFromRawDataWhenNoInnerAssets(): void
    {
        $assetName = 'style.css';
        $cssContent = 'body { background: #cccccc; }';
        $stream = Utils::streamFor($cssContent);
        $pdfTemplateAsset = new PdfTemplateAsset($assetName, null, $stream);

        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromRawData')
            ->with($cssContent, $assetName)
            ->willReturn($pdfTemplateAsset);

        $result = $this->factory->createFromRawData($cssContent, $assetName);
        self::assertSame($pdfTemplateAsset, $result);
    }

    public function testCreateFromRawDataExtractsInnerAssets(): void
    {
        $assetName = 'style.css';
        $cssContent = "body { background: url('images/bg.png'); }";
        $stream = Utils::streamFor($cssContent);
        $pdfTemplateAsset = new PdfTemplateAsset($assetName, null, $stream);

        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromRawData')
            ->with($cssContent, $assetName)
            ->willReturn($pdfTemplateAsset);

        $innerAssetName = 'images__bg.png';
        $innerFilepath = 'images/bg.png';
        $innerPdfTemplateAsset = new PdfTemplateAsset(
            $innerAssetName,
            null,
            $this->createMock(StreamInterface::class)
        );

        $this->pdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromPath')
            ->with($innerFilepath, $innerAssetName)
            ->willReturn($innerPdfTemplateAsset);

        $newStream = $this->createMock(StreamInterface::class);
        $newPdfTemplateAsset = new PdfTemplateAsset(
            $assetName,
            null,
            $newStream,
            [$innerPdfTemplateAsset->getName() => $innerPdfTemplateAsset]
        );
        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromStream')
            ->with(
                self::isInstanceOf(StreamInterface::class),
                $assetName,
                [$innerPdfTemplateAsset->getName() => $innerPdfTemplateAsset]
            )
            ->willReturn($newPdfTemplateAsset);

        $result = $this->factory->createFromRawData($cssContent, $assetName);
        self::assertSame($newPdfTemplateAsset, $result);
    }

    public function testCreateFromRawDataThrowsExceptionOnUnreadableStream(): void
    {
        $assetName = 'style.css';
        $cssContent = "body { background: url('images/bg.png'); }";
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->expects(self::once())
            ->method('isReadable')
            ->willReturn(false);

        $pdfTemplateAsset = new PdfTemplateAsset($assetName, null, $stream);
        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromRawData')
            ->willReturn($pdfTemplateAsset);

        $this->expectException(PdfTemplateAssetException::class);
        $this->expectExceptionMessage(
            'Impossible to extract inner assets from the non-readable/non-seekable PDF template asset "style.css"'
        );

        $this->factory->createFromRawData($cssContent, $assetName);
    }

    public function testCreateFromStreamWhenNoInnerAssets(): void
    {
        $assetName = 'style.css';
        $cssContent = 'body { background: #cccccc; }';
        $stream = Utils::streamFor($cssContent);
        $pdfTemplateAsset = new PdfTemplateAsset($assetName, null, $stream);

        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromStream')
            ->with($stream, $assetName)
            ->willReturn($pdfTemplateAsset);

        $result = $this->factory->createFromStream($stream, $assetName);
        self::assertSame($pdfTemplateAsset, $result);
    }

    public function testCreateFromStreamExtractsInnerAssets(): void
    {
        $assetName = 'style.css';
        $cssContent = "body { background: url('images/bg.png'); }";
        $stream = Utils::streamFor($cssContent);
        $pdfTemplateAsset = new PdfTemplateAsset($assetName, null, $stream);

        $innerAssetName = 'images__bg.png';
        $innerFilepath = 'images/bg.png';
        $innerPdfTemplateAsset = new PdfTemplateAsset(
            $innerAssetName,
            null,
            $this->createMock(StreamInterface::class)
        );

        $this->pdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromPath')
            ->with($innerFilepath, $innerAssetName)
            ->willReturn($innerPdfTemplateAsset);

        $newStream = $this->createMock(StreamInterface::class);
        $newPdfTemplateAsset = new PdfTemplateAsset(
            $assetName,
            null,
            $newStream,
            [$innerPdfTemplateAsset->getName() => $innerPdfTemplateAsset]
        );

        $this->basicPdfTemplateAssetFactory
            ->expects(self::exactly(2))
            ->method('createFromStream')
            ->withConsecutive(
                [$stream, $assetName],
                [
                    self::isInstanceOf(StreamInterface::class),
                    $assetName,
                    [$innerPdfTemplateAsset->getName() => $innerPdfTemplateAsset],
                ]
            )
            ->willReturnOnConsecutiveCalls($pdfTemplateAsset, $newPdfTemplateAsset);

        $result = $this->factory->createFromStream($stream, $assetName);
        self::assertSame($newPdfTemplateAsset, $result);
    }

    public function testCreateFromStreamThrowsExceptionOnUnreadableStream(): void
    {
        $assetName = 'style.css';
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->expects(self::once())
            ->method('isReadable')
            ->willReturn(false);

        $pdfTemplateAsset = new PdfTemplateAsset($assetName, null, $stream);
        $this->basicPdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromStream')
            ->willReturn($pdfTemplateAsset);

        $this->expectException(PdfTemplateAssetException::class);
        $this->expectExceptionMessage(
            'Impossible to extract inner assets from the non-readable/non-seekable PDF template asset "style.css"'
        );

        $this->factory->createFromStream($stream, $assetName);
    }

    public function testIsApplicableReturnsTrueForCssFile(): void
    {
        self::assertTrue($this->factory->isApplicable('style.css', null, null));
    }

    public function testIsApplicableReturnsFalseForNonCssFile(): void
    {
        self::assertFalse($this->factory->isApplicable('style.js', null, null));
    }
}
