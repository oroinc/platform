<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Provider;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;

/**
 * Interface for finding a PDF document by source entity.
 */
interface SinglePdfDocumentBySourceEntityProviderInterface
{
    /**
     * Finds a single PDF document by source entity.
     *
     * @param object $sourceEntity The source entity to retrieve PDF documents for.
     * @param string $pdfDocumentName The name of the PDF document (e.g., order-0101).
     * @param string $pdfDocumentType The type of the PDF document (e.g., us_standard_invoice).
     *
     * @return AbstractPdfDocument|null The retrieved PDF document or null if not found.
     */
    public function findPdfDocument(
        object $sourceEntity,
        string $pdfDocumentName,
        string $pdfDocumentType
    ): ?AbstractPdfDocument;
}
