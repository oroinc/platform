<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand;

/**
 * An abstract demand for generating a PDF document.
 */
abstract class AbstractPdfDocumentDemand
{
    /**
     * The entity for which to generate the PDF document.
     */
    protected object $sourceEntity;

    /**
     * The name of the PDF document (e.g., order-0101).
     */
    protected ?string $pdfDocumentName;

    /**
     * The type of the PDF document (e.g., us_standard_invoice).
     */
    protected ?string $pdfDocumentType;

    /**
     * The PDF options preset name (e.g., default, letter, a4, etc.).
     */
    protected ?string $pdfOptionsPreset;

    /**
     * The arbitrary payload data to be passed to the PDF generator.
     */
    protected array $pdfDocumentPayload = [];

    /**
     * Returns the entity for which the PDF document is generated.
     */
    public function getSourceEntity(): object
    {
        return $this->sourceEntity;
    }

    /**
     * Returns the name of the PDF document (e.g., order-0101).
     */
    public function getPdfDocumentName(): string
    {
        return $this->pdfDocumentName;
    }

    /**
     * Returns the type of the PDF document (e.g., us_standard_invoice).
     */
    public function getPdfDocumentType(): string
    {
        return $this->pdfDocumentType;
    }

    /**
     * Returns the PDF options preset name (e.g., default, letter, a4, etc.).
     */
    public function getPdfOptionsPreset(): string
    {
        return $this->pdfOptionsPreset;
    }

    /**
     * Sets the PDF options preset name (e.g., default, default_a4, etc.).
     */
    public function setPdfOptionsPreset(string $pdfOptionsPreset): void
    {
        $this->pdfOptionsPreset = $pdfOptionsPreset;
    }

    /**
     * Returns arbitrary payload data to be passed to the PDF generator.
     */
    public function getPdfDocumentPayload(): array
    {
        return $this->pdfDocumentPayload;
    }

    /**
     * Sets the arbitrary payload data to be passed to the PDF generator.
     *
     * @param array|null $pdfDocumentPayload
     */
    public function setPdfDocumentPayload(?array $pdfDocumentPayload): void
    {
        $this->pdfDocumentPayload = $pdfDocumentPayload;
    }
}
