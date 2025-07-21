<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand;

use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;

/**
 * Represents a generic demand for generating a PDF document.
 */
class GenericPdfDocumentDemand extends AbstractPdfDocumentDemand
{
    /**
     * @param object $sourceEntity The entity for which to generate the PDF document.
     * @param string $pdfDocumentName The name of the PDF document (e.g., order-0101).
     * @param string $pdfDocumentType The type of the PDF document (e.g., us_standard_invoice).
     * @param string $pdfOptionsPreset The PDF options preset name (e.g., default, letter, a4, etc.).
     * @param array $pdfDocumentPayload The arbitrary payload data to be passed to the PDF generator.
     */
    public function __construct(
        object $sourceEntity,
        string $pdfDocumentName,
        string $pdfDocumentType,
        string $pdfOptionsPreset = PdfOptionsPreset::DEFAULT,
        array $pdfDocumentPayload = []
    ) {
        $this->sourceEntity = $sourceEntity;
        $this->pdfDocumentName = $pdfDocumentName;
        $this->pdfDocumentType = $pdfDocumentType;
        $this->pdfOptionsPreset = $pdfOptionsPreset;
        $this->pdfDocumentPayload = $pdfDocumentPayload;
    }
}
