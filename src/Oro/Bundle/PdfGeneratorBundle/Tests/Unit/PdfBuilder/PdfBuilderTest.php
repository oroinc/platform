<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfBuilder;

use Oro\Bundle\PdfGeneratorBundle\Event\AfterPdfOptionsResolvedEvent;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfOptionsResolvedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilder;
use Oro\Bundle\PdfGeneratorBundle\PdfEngine\PdfEngineInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptions;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;
use Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Stub\PdfEngineStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PdfBuilderTest extends TestCase
{
    private PdfEngineInterface $pdfEngine;

    private PdfOptions $pdfOptions;

    private PdfBuilder $pdfBuilder;

    private PdfFileInterface&MockObject $pdfFile;

    private EventDispatcherInterface $eventDispatcher;

    private array $definedOptions = [
        'header',
        'footer',
        'content',
        'assets',
        'scale',
        'landscape',
        'page_width',
        'page_height',
        'margin_top',
        'margin_bottom',
        'margin_left',
        'margin_right',
        'custom_options',
    ];

    private array $defaultOptions = [
        'assets' => [],
        'custom_options' => [],
    ];

    protected function setUp(): void
    {
        $this->pdfFile = $this->createMock(PdfFileInterface::class);
        $this->pdfEngine = new PdfEngineStub($this->pdfFile);
        $this->pdfOptions = new PdfOptions(
            [],
            (new OptionsResolver())
                ->setDefined($this->definedOptions)
                ->setDefaults($this->defaultOptions)
        );

        $this->pdfBuilder = new PdfBuilder($this->pdfEngine, $this->pdfOptions);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->pdfBuilder->setEventDispatcher($this->eventDispatcher);
    }

    public function testContent(): void
    {
        $pdfTemplate = $this->createMock(PdfTemplateInterface::class);
        $this->pdfBuilder->content($pdfTemplate);

        self::assertSame($pdfTemplate, $this->pdfOptions['content']);
    }

    public function testHeader(): void
    {
        $pdfTemplate = $this->createMock(PdfTemplateInterface::class);
        $this->pdfBuilder->header($pdfTemplate);

        self::assertSame($pdfTemplate, $this->pdfOptions['header']);
    }

    public function testFooter(): void
    {
        $pdfTemplate = $this->createMock(PdfTemplateInterface::class);
        $this->pdfBuilder->footer($pdfTemplate);

        self::assertSame($pdfTemplate, $this->pdfOptions['footer']);
    }

    public function testAsset(): void
    {
        $asset1 = $this->createMock(PdfTemplateAssetInterface::class);
        $asset2 = $this->createMock(PdfTemplateAssetInterface::class);

        $this->pdfBuilder->asset($asset1);
        self::assertContains($asset1, $this->pdfOptions['assets']);

        $this->pdfBuilder->asset($asset2);
        self::assertContains($asset1, $this->pdfOptions['assets']);
        self::assertContains($asset2, $this->pdfOptions['assets']);
    }

    public function testPageSize(): void
    {
        $this->pdfBuilder->pageSize(210, 297);

        self::assertSame(210, $this->pdfOptions['page_width']);
        self::assertSame(297, $this->pdfOptions['page_height']);
    }

    public function testMargins(): void
    {
        $this->pdfBuilder->margins(10, 15, 20, 25);

        self::assertSame(10, $this->pdfOptions['margin_top']);
        self::assertSame(15, $this->pdfOptions['margin_bottom']);
        self::assertSame(20, $this->pdfOptions['margin_left']);
        self::assertSame(25, $this->pdfOptions['margin_right']);
    }

    public function testLandscape(): void
    {
        $this->pdfBuilder->landscape(true);

        self::assertTrue($this->pdfOptions['landscape']);
    }

    public function testScale(): void
    {
        $this->pdfBuilder->scale(1.5);

        self::assertSame(1.5, $this->pdfOptions['scale']);
    }

    public function testCustomOption(): void
    {
        $this->pdfBuilder->customOption('custom_key1', 'custom_value1');
        self::assertSame('custom_value1', $this->pdfOptions['custom_options']['custom_key1']);

        $this->pdfBuilder->customOption('custom_key2', 'custom_value2');
        self::assertSame('custom_value1', $this->pdfOptions['custom_options']['custom_key1']);
        self::assertSame('custom_value2', $this->pdfOptions['custom_options']['custom_key2']);
    }

    public function testOption(): void
    {
        $this->pdfBuilder->option('scale', '0.5');

        self::assertSame('0.5', $this->pdfOptions['scale']);
    }

    public function testCreatePdfFile(): void
    {
        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [new BeforePdfOptionsResolvedEvent($this->pdfOptions, $this->pdfEngine::getName())],
                [new AfterPdfOptionsResolvedEvent($this->pdfOptions, $this->pdfEngine::getName())]
            );

        self::assertSame($this->pdfFile, $this->pdfBuilder->createPdfFile());
    }

    public function testCreatePdfFileWhenNoEventDispatcher(): void
    {
        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->pdfBuilder->setEventDispatcher(null);

        self::assertSame($this->pdfFile, $this->pdfBuilder->createPdfFile());
    }
}
