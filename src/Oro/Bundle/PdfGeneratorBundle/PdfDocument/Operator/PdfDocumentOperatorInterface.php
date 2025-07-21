<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Operator;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\AbstractPdfDocumentDemand;

/**
 * Interface for a PDF document operator.
 */
interface PdfDocumentOperatorInterface
{
    /**
     * Creates a new PDF document based on the provided demand.
     */
    public function createPdfDocument(AbstractPdfDocumentDemand $pdfDocumentDemand): AbstractPdfDocument;

    /**
     * Re-generates the PDF file for the existing PDF document.
     */
    public function updatePdfDocument(AbstractPdfDocument $pdfDocument): void;

    /**
     * Checks if the PDF document is resolved (e.g., the PDF file is generated).
     *
     * @param AbstractPdfDocument $pdfDocument The PDF document to check.
     *
     * @return bool True if the PDF document is resolved, false otherwise.
     */
    public function isResolvedPdfDocument(AbstractPdfDocument $pdfDocument): bool;

    /**
     * Resolves the PDF document to ensure it is up-to-date, e.g. generates the PDF file if it is missing.
     */
    public function resolvePdfDocument(AbstractPdfDocument $pdfDocument): void;

    /**
     * Deletes the PDF document.
     */
    public function deletePdfDocument(AbstractPdfDocument $pdfDocument): void;
}
