<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Generator;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;

/**
 * Interface for generating a PDF file for given PDF document.
 */
interface PdfDocumentGeneratorInterface
{
    /**
     * Checks if the generator is applicable for the given PDF document.
     */
    public function isApplicable(AbstractPdfDocument $pdfDocument): bool;

    /**
     * Generates a PDF file for the given PDF document.
     */
    public function generatePdfFile(AbstractPdfDocument $pdfDocument): PdfFileInterface;
}
