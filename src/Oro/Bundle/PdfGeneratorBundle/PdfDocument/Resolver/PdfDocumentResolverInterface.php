<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Resolver;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;

/**
 * Interface for PDF document resolver.
 */
interface PdfDocumentResolverInterface
{
    /**
     * Resolves the PDF document to ensure it is up-to-date, e.g. generates the PDF file if it is missing.
     *
     * @param AbstractPdfDocument $pdfDocument
     */
    public function resolvePdfDocument(AbstractPdfDocument $pdfDocument): void;

    /**
     * Checks if the resolver is applicable.
     *
     * @param AbstractPdfDocument $pdfDocument
     *
     * @return bool True if applicable, false otherwise.
     */
    public function isApplicable(AbstractPdfDocument $pdfDocument): bool;
}
