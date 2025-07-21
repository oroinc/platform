<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Event;

use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched before the PDF document file is generated.
 */
class BeforePdfDocumentGeneratedEvent extends Event
{
    /**
     * @param PdfBuilderInterface $pdfBuilder
     * @param AbstractPdfDocument $pdfDocument
     * @param array $pdfDocumentPayload The arbitrary payload data to be passed to PDF template.
     */
    public function __construct(
        private readonly PdfBuilderInterface $pdfBuilder,
        private readonly AbstractPdfDocument $pdfDocument,
        private array $pdfDocumentPayload
    ) {
    }

    public function getPdfBuilder(): PdfBuilderInterface
    {
        return $this->pdfBuilder;
    }

    public function getPdfDocument(): AbstractPdfDocument
    {
        return $this->pdfDocument;
    }

    /**
     * @return array The arbitrary payload data to be passed to PDF template.
     */
    public function getPdfDocumentPayload(): array
    {
        return $this->pdfDocumentPayload;
    }

    /**
     * @param array $pdfDocumentPayload The arbitrary payload data to be passed to PDF template.
     */
    public function setPdfDocumentPayload(array $pdfDocumentPayload): void
    {
        $this->pdfDocumentPayload = $pdfDocumentPayload;
    }
}
