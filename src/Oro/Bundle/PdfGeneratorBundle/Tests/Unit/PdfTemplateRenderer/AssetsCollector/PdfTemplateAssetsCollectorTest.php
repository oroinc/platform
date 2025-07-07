<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfTemplateRenderer\AssetsCollector;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAsset;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\AssetsCollector\PdfTemplateAssetsCollector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

final class PdfTemplateAssetsCollectorTest extends TestCase
{
    private PdfTemplateAssetFactoryInterface&MockObject $pdfTemplateAssetFactory;

    private PdfTemplateAssetsCollector $collector;

    protected function setUp(): void
    {
        $this->pdfTemplateAssetFactory = $this->createMock(PdfTemplateAssetFactoryInterface::class);

        $this->collector = new PdfTemplateAssetsCollector($this->pdfTemplateAssetFactory);
    }

    public function testGetAssetsInitiallyEmpty(): void
    {
        self::assertSame([], $this->collector->getAssets());
    }

    public function testAddStaticAsset(): void
    {
        $assetPath = 'path/to/asset.css';
        $assetName = 'asset.css';
        $pdfTemplateAsset = new PdfTemplateAsset($assetName, $assetPath, $this->createMock(StreamInterface::class));

        $this->pdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromPath')
            ->with($assetPath)
            ->willReturn($pdfTemplateAsset);

        $returnedName = $this->collector->addStaticAsset($assetPath);

        self::assertSame($assetName, $returnedName);
        self::assertSame([$assetName => $pdfTemplateAsset], $this->collector->getAssets());
    }

    public function testAddRawAsset(): void
    {
        $data = '<style>body { color: red; }</style>';
        $name = 'style.css';
        $pdfTemplateAsset = new PdfTemplateAsset($name, null, $this->createMock(StreamInterface::class));

        $this->pdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromRawData')
            ->with($data, $name)
            ->willReturn($pdfTemplateAsset);

        $returnedName = $this->collector->addRawAsset($data, $name);

        self::assertSame($name, $returnedName);
        self::assertSame([$name => $pdfTemplateAsset], $this->collector->getAssets());
    }

    public function testReset(): void
    {
        $this->collector->addStaticAsset('path/to/asset.css');
        self::assertNotEmpty($this->collector->getAssets());

        $this->collector->reset();
        self::assertSame([], $this->collector->getAssets());
    }
}
