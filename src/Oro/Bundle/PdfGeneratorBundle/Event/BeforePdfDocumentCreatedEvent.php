<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Event;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\AbstractPdfDocumentDemand;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched before the PDF document entity is created.
 */
class BeforePdfDocumentCreatedEvent extends Event
{
    /**
     * @param AbstractPdfDocumentDemand $pdfDocumentDemand
     * @param string $pdfDocumentGenerationMode The PDF document generation mode, {@see PdfDocumentGenerationMode}.
     */
    public function __construct(
        private readonly AbstractPdfDocumentDemand $pdfDocumentDemand,
        private readonly string $pdfDocumentGenerationMode
    ) {
    }

    public function getPdfDocumentDemand(): AbstractPdfDocumentDemand
    {
        return $this->pdfDocumentDemand;
    }

    /**
     * @return string The PDF document generation mode, {@see PdfDocumentGenerationMode}.
     */
    public function getPdfDocumentGenerationMode(): string
    {
        return $this->pdfDocumentGenerationMode;
    }
}
