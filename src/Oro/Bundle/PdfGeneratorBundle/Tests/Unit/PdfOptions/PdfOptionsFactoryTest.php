<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfOptions;

use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptions;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsFactory;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPresetConfiguratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PdfOptionsFactoryTest extends TestCase
{
    private PdfOptionsPresetConfiguratorInterface&MockObject $pdfOptionsPresetConfigurator;

    private PdfOptionsFactory $pdfOptionsFactory;

    protected function setUp(): void
    {
        $this->pdfOptionsPresetConfigurator = $this->createMock(PdfOptionsPresetConfiguratorInterface::class);
        $this->pdfOptionsFactory = new PdfOptionsFactory([$this->pdfOptionsPresetConfigurator]);
    }

    public function testCreatePdfOptionsWithoutApplicableConfigurator(): void
    {
        $pdfEngineName = 'sample_engine';

        $this->pdfOptionsPresetConfigurator
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfEngineName, PdfOptionsPreset::DEFAULT)
            ->willReturn(false);

        $pdfOptions = $this->pdfOptionsFactory->createPdfOptions($pdfEngineName);

        self::assertInstanceOf(PdfOptions::class, $pdfOptions);
        self::assertSame(PdfOptionsPreset::DEFAULT, $pdfOptions->getPreset());
        self::assertSame([], $pdfOptions->toArray());
    }

    public function testCreatePdfOptionsWithApplicableConfigurator(): void
    {
        $pdfEngineName = 'sample_engine';
        $pdfOptionsPreset = 'preset';

        $this->pdfOptionsPresetConfigurator
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfEngineName, $pdfOptionsPreset)
            ->willReturn(true);

        $this->pdfOptionsPresetConfigurator
            ->expects(self::once())
            ->method('configureOptions')
            ->with(self::isInstanceOf(OptionsResolver::class));

        $pdfOptions = $this->pdfOptionsFactory->createPdfOptions($pdfEngineName, $pdfOptionsPreset);

        self::assertInstanceOf(PdfOptions::class, $pdfOptions);
        self::assertSame($pdfOptionsPreset, $pdfOptions->getPreset());
        self::assertSame([], $pdfOptions->toArray());
    }
}
