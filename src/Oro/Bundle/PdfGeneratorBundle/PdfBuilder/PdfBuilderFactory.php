<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfBuilder;

use Oro\Bundle\PdfGeneratorBundle\Exception\PdfGeneratorException;
use Oro\Bundle\PdfGeneratorBundle\PdfEngine\PdfEngineRegistry;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Creates a PDF builder for the specified PDF options preset.
 */
class PdfBuilderFactory implements PdfBuilderFactoryInterface
{
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        private PdfEngineRegistry $pdfEngineRegistry,
        private PdfOptionsFactoryInterface $pdfOptionsFactory,
        private string $pdfEngineName
    ) {
    }

    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws PdfGeneratorException
     */
    #[\Override]
    public function createPdfBuilder(string $pdfOptionsPreset = PdfOptionsPreset::DEFAULT): PdfBuilderInterface
    {
        $pdfEngine = $this->pdfEngineRegistry->getPdfEngine($this->pdfEngineName);
        $pdfOptions = $this->pdfOptionsFactory->createPdfOptions($this->pdfEngineName, $pdfOptionsPreset);

        $pdfBuilder = new PdfBuilder($pdfEngine, $pdfOptions);
        $pdfBuilder->setEventDispatcher($this->eventDispatcher);

        return $pdfBuilder;
    }
}
