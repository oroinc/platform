<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfBuilder;

use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilder;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderFactory;
use Oro\Bundle\PdfGeneratorBundle\PdfEngine\PdfEngineInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfEngine\PdfEngineRegistry;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class PdfBuilderFactoryTest extends TestCase
{
    private PdfBuilderFactory $factory;

    private PdfEngineRegistry&MockObject $pdfEngineRegistry;

    private PdfOptionsFactoryInterface&MockObject $pdfOptionsFactory;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private string $pdfEngineName;

    protected function setUp(): void
    {
        $this->pdfEngineRegistry = $this->createMock(PdfEngineRegistry::class);
        $this->pdfOptionsFactory = $this->createMock(PdfOptionsFactoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->pdfEngineName = 'test_engine';

        $this->factory = new PdfBuilderFactory(
            $this->pdfEngineRegistry,
            $this->pdfOptionsFactory,
            $this->pdfEngineName
        );
        $this->factory->setEventDispatcher($this->eventDispatcher);
    }

    public function testCreatePdfBuilder(): void
    {
        $pdfEngine = $this->createMock(PdfEngineInterface::class);
        $pdfOptions = $this->createMock(PdfOptionsInterface::class);

        $this->pdfEngineRegistry
            ->expects(self::once())
            ->method('getPdfEngine')
            ->with($this->pdfEngineName)
            ->willReturn($pdfEngine);

        $this->pdfOptionsFactory
            ->expects(self::once())
            ->method('createPdfOptions')
            ->with($this->pdfEngineName, PdfOptionsPreset::DEFAULT)
            ->willReturn($pdfOptions);

        $pdfBuilder = $this->factory->createPdfBuilder();

        $expectedPdfBuilder = new PdfBuilder($pdfEngine, $pdfOptions);
        $expectedPdfBuilder->setEventDispatcher($this->eventDispatcher);

        self::assertEquals($expectedPdfBuilder, $pdfBuilder);
    }

    public function testCreatePdfBuilderWhenNoEventDispatcher(): void
    {
        $pdfEngine = $this->createMock(PdfEngineInterface::class);
        $pdfOptions = $this->createMock(PdfOptionsInterface::class);

        $this->pdfEngineRegistry
            ->expects(self::once())
            ->method('getPdfEngine')
            ->with($this->pdfEngineName)
            ->willReturn($pdfEngine);

        $this->pdfOptionsFactory
            ->expects(self::once())
            ->method('createPdfOptions')
            ->with($this->pdfEngineName, PdfOptionsPreset::DEFAULT)
            ->willReturn($pdfOptions);

        $this->factory->setEventDispatcher(null);
        $pdfBuilder = $this->factory->createPdfBuilder();

        $expectedPdfBuilder = new PdfBuilder($pdfEngine, $pdfOptions);

        self::assertEquals($expectedPdfBuilder, $pdfBuilder);
    }
}
