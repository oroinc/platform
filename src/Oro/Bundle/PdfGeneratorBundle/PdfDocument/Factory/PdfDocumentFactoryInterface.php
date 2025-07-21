<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Factory;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\AbstractPdfDocumentDemand;

/**
 * Interface for the factory that creates a PDF document entity from a demand.
 */
interface PdfDocumentFactoryInterface
{
    /**
     * Creates a PDF document based on the provided demand.
     *
     * @param AbstractPdfDocumentDemand $pdfDocumentDemand The demand containing details for PDF generation.
     *
     * @return AbstractPdfDocument
     */
    public function createPdfDocument(AbstractPdfDocumentDemand $pdfDocumentDemand): AbstractPdfDocument;
}
