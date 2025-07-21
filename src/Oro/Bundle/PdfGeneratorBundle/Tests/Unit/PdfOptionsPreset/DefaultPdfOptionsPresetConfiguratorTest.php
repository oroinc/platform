<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfOptionsPreset;

use Oro\Bundle\PdfGeneratorBundle\Model\Size;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\DefaultPdfOptionsPresetConfigurator;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DefaultPdfOptionsPresetConfiguratorTest extends TestCase
{
    private PdfTemplateAssetFactoryInterface&MockObject $pdfTemplateAssetFactory;

    private DefaultPdfOptionsPresetConfigurator $configurator;

    private OptionsResolver $resolver;

    protected function setUp(): void
    {
        $this->pdfTemplateAssetFactory = $this->createMock(PdfTemplateAssetFactoryInterface::class);
        $this->configurator = new DefaultPdfOptionsPresetConfigurator($this->pdfTemplateAssetFactory);
        $this->resolver = new OptionsResolver();
        $this->configurator->configureOptions($this->resolver);
    }

    public function testMinimumRequiredOptions(): void
    {
        $options = [
            'content' => $this->createMock(PdfTemplateInterface::class),
        ];

        $resolvedOptions = $this->resolver->resolve($options);

        self::assertInstanceOf(PdfTemplateInterface::class, $resolvedOptions['content']);
    }

    public function testAssetsOption(): void
    {
        $pdfTemplate = $this->createMock(PdfTemplateInterface::class);
        $pdfTemplateAsset = $this->createMock(PdfTemplateAssetInterface::class);
        $options = [
            'content' => $pdfTemplate,
            'assets' => [$pdfTemplateAsset],
        ];

        $resolvedOptions = $this->resolver->resolve($options);

        self::assertSame($pdfTemplate, $resolvedOptions['content']);
        self::assertSame([$pdfTemplateAsset], $resolvedOptions['assets']);
    }

    public function testAssetsOptionCreatesPdfTemplateAssetWhenStringIsPassed(): void
    {
        $pdfTemplate = $this->createMock(PdfTemplateInterface::class);
        $assetPath = '@Acme/sample.html.twig';
        $options = [
            'content' => $pdfTemplate,
            'assets' => [$assetPath],
        ];

        $pdfTemplateAsset = $this->createMock(PdfTemplateAssetInterface::class);
        $this->pdfTemplateAssetFactory
            ->expects(self::once())
            ->method('createFromPath')
            ->with($assetPath)
            ->willReturn($pdfTemplateAsset);

        $resolvedOptions = $this->resolver->resolve($options);

        self::assertSame($pdfTemplate, $resolvedOptions['content']);
        self::assertCount(1, $resolvedOptions['assets']);
        self::assertSame($pdfTemplateAsset, $resolvedOptions['assets'][0]);
    }

    public function testContentHeaderFooterOptions(): void
    {
        $contentTemplate = $this->createMock(PdfTemplateInterface::class);
        $headerTemplate = $this->createMock(PdfTemplateInterface::class);
        $footerTemplate = $this->createMock(PdfTemplateInterface::class);
        $options = [
            'content' => $contentTemplate,
            'header' => $headerTemplate,
            'footer' => $footerTemplate,
        ];

        $resolvedOptions = $this->resolver->resolve($options);

        self::assertSame($contentTemplate, $resolvedOptions['content']);
        self::assertSame($headerTemplate, $resolvedOptions['header']);
        self::assertSame($footerTemplate, $resolvedOptions['footer']);
    }

    /**
     * @dataProvider sizeDataProvider
     */
    public function testPageSizeOptions(Size|string|int|float $size): void
    {
        $options = [
            'content' => $this->createMock(PdfTemplateInterface::class),
            'page_width' => $size,
            'page_height' => $size,
        ];

        $resolvedOptions = $this->resolver->resolve($options);

        self::assertEquals(Size::create($size), $resolvedOptions['page_width']);
        self::assertEquals(Size::create($size), $resolvedOptions['page_height']);
    }

    private function sizeDataProvider(): \Generator
    {
        yield [Size::create(0)];
        yield ['0'];
        yield [0];
        yield [0.0];

        yield [Size::create(1234)];
        yield ['1234'];
        yield [1234];
        yield [1234.5678];

        yield [Size::create('1234.5678in')];
        yield ['1234.5678in'];

        yield [Size::create('1234.5678pt')];
        yield ['1234.5678pt'];

        yield [Size::create('1234.5678px')];
        yield ['1234.5678px'];

        yield [Size::create('1234.5678mm')];
        yield ['1234.5678mm'];

        yield [Size::create('1234.5678cm')];
        yield ['1234.5678cm'];

        yield [Size::create('1234.5678pc')];
        yield ['1234.5678pc'];
    }

    /**
     * @dataProvider numberProvider
     */
    public function testScaleAndLandscapeOptions(string|int|float $number, float $expected): void
    {
        $options = [
            'content' => $this->createMock(PdfTemplateInterface::class),
            'landscape' => true,
            'scale' => $number,
        ];

        $resolvedOptions = $this->resolver->resolve($options);

        self::assertTrue($resolvedOptions['landscape']);
        self::assertSame($expected, $resolvedOptions['scale']);
    }

    private function numberProvider(): \Generator
    {
        yield ['0.9', 0.9];
        yield [1, 1.0];
        yield [0.9, 0.9];
    }

    public function testLandscapeOption(): void
    {
        $options = [
            'content' => $this->createMock(PdfTemplateInterface::class),
            'landscape' => true,
        ];

        $resolvedOptions = $this->resolver->resolve($options);

        self::assertTrue($resolvedOptions['landscape']);
    }

    /**
     * @dataProvider sizeDataProvider
     */
    public function testMarginOptions(Size|string|int|float $size): void
    {
        $options = [
            'content' => $this->createMock(PdfTemplateInterface::class),
            'margin_top' => $size,
            'margin_right' => $size,
            'margin_bottom' => $size,
            'margin_left' => $size,
        ];

        $resolvedOptions = $this->resolver->resolve($options);

        self::assertEquals(Size::create($size), $resolvedOptions['margin_top']);
        self::assertEquals(Size::create($size), $resolvedOptions['margin_right']);
        self::assertEquals(Size::create($size), $resolvedOptions['margin_bottom']);
        self::assertEquals(Size::create($size), $resolvedOptions['margin_left']);
    }

    public function testCustomOptions(): void
    {
        $options = [
            'content' => $this->createMock(PdfTemplateInterface::class),
            'custom_options' => ['custom' => 'value'],
        ];

        $resolvedOptions = $this->resolver->resolve($options);

        self::assertSame(['custom' => 'value'], $resolvedOptions['custom_options']);
    }

    public function testIsApplicableAlwaysReturnsTrue(): void
    {
        self::assertTrue($this->configurator->isApplicable('some_engine'));
    }
}
