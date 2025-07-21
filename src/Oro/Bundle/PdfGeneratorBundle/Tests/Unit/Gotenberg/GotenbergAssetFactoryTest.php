<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Gotenberg;

use Gotenberg\Stream;
use Oro\Bundle\PdfGeneratorBundle\Gotenberg\GotenbergAssetFactory;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAsset;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

final class GotenbergAssetFactoryTest extends TestCase
{
    private GotenbergAssetFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new GotenbergAssetFactory();
    }

    public function testCreateFromPdfTemplateAssetWithNoInnerAssets(): void
    {
        $assetName = 'main.css';
        $stream = $this->createMock(StreamInterface::class);

        $pdfTemplateAsset = new PdfTemplateAsset($assetName, null, $stream, []);

        $result = $this->factory->createFromPdfTemplateAsset($pdfTemplateAsset);

        self::assertCount(1, $result);
        self::assertArrayHasKey($assetName, $result);
        self::assertEquals(new Stream($assetName, $stream), $result[$assetName]);
    }

    public function testCreateFromPdfTemplateAssetWithInnerAssets(): void
    {
        $mainAssetName = 'main.css';
        $innerAssetName = 'font.woff';

        $mainStream = $this->createMock(StreamInterface::class);
        $innerStream = $this->createMock(StreamInterface::class);

        $innerAsset = new PdfTemplateAsset($innerAssetName, null, $innerStream, []);

        $pdfTemplateAsset = new PdfTemplateAsset($mainAssetName, null, $mainStream, [$innerAsset]);

        $result = $this->factory->createFromPdfTemplateAsset($pdfTemplateAsset);

        self::assertCount(2, $result);
        self::assertArrayHasKey($mainAssetName, $result);
        self::assertArrayHasKey($innerAssetName, $result);
        self::assertEquals(new Stream($mainAssetName, $mainStream), $result[$mainAssetName]);
        self::assertEquals(new Stream($innerAssetName, $innerStream), $result[$innerAssetName]);
    }

    public function testCreateFromPdfTemplateAssetWithNestedInnerAssets(): void
    {
        $mainAssetName = 'main.html';
        $innerAsset1Name = 'inner.css';
        $innerAsset2Name = 'font.woff';

        $mainStream = $this->createMock(StreamInterface::class);
        $innerStream1 = $this->createMock(StreamInterface::class);
        $innerStream2 = $this->createMock(StreamInterface::class);

        $innerAsset2 = new PdfTemplateAsset($innerAsset2Name, null, $innerStream2, []);
        $innerAsset1 = new PdfTemplateAsset($innerAsset1Name, null, $innerStream1, [$innerAsset2]);
        $pdfTemplateAsset = new PdfTemplateAsset($mainAssetName, null, $mainStream, [$innerAsset1]);

        $result = $this->factory->createFromPdfTemplateAsset($pdfTemplateAsset);

        self::assertCount(3, $result);
        self::assertArrayHasKey($mainAssetName, $result);
        self::assertArrayHasKey($innerAsset1Name, $result);
        self::assertArrayHasKey($innerAsset2Name, $result);
        self::assertEquals(new Stream($mainAssetName, $mainStream), $result[$mainAssetName]);
        self::assertEquals(new Stream($innerAsset1Name, $innerStream1), $result[$innerAsset1Name]);
        self::assertEquals(new Stream($innerAsset2Name, $innerStream2), $result[$innerAsset2Name]);
    }
}
