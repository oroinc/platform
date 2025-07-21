<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Represents a PDF document as a model.
 */
abstract class AbstractPdfDocument
{
    protected string $uuid;

    protected string $pdfDocumentName;

    protected string $pdfDocumentType;

    protected ?string $sourceEntityClass = null;

    protected ?int $sourceEntityId = null;

    protected ?array $pdfDocumentPayload = null;

    protected string $pdfOptionsPreset;

    protected string $pdfDocumentState;

    protected string $pdfDocumentGenerationMode;

    protected ?File $pdfDocumentFile = null;

    public function __construct()
    {
        $this->uuid = UUIDGenerator::v4();
    }

    /**
     * Returns the universally unique identifier (UUID), e.g. to identify the document in URL.
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Sets the universally unique identifier (UUID), e.g. to identify the document in URL.
     */
    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Returns the name of the PDF document (e.g., order-0101).
     */
    public function getPdfDocumentName(): string
    {
        return $this->pdfDocumentName;
    }

    /**
     * Sets the name of the PDF document (e.g., order-0101).
     */
    public function setPdfDocumentName(string $pdfDocumentName): self
    {
        $this->pdfDocumentName = $pdfDocumentName;

        return $this;
    }

    /**
     * Returns the type of the PDF document (e.g., us_standard_invoice).
     */
    public function getPdfDocumentType(): string
    {
        return $this->pdfDocumentType;
    }

    /**
     * Sets the type of the PDF document (e.g., us_standard_invoice).
     */
    public function setPdfDocumentType(string $pdfDocumentType): self
    {
        $this->pdfDocumentType = $pdfDocumentType;

        return $this;
    }

    /**
     * Returns the class name of the entity for which the PDF document is generated.
     */
    public function getSourceEntityClass(): ?string
    {
        return $this->sourceEntityClass;
    }

    /**
     * Sets the class name of the entity for which the PDF document is generated.
     */
    public function setSourceEntityClass(?string $sourceEntityClass): self
    {
        $this->sourceEntityClass = $sourceEntityClass;

        return $this;
    }

    /**
     * Returns the ID of the entity for which the PDF document is generated.
     */
    public function getSourceEntityId(): ?int
    {
        return $this->sourceEntityId;
    }

    /**
     * Sets the ID of the entity for which the PDF document is generated.
     */
    public function setSourceEntityId(?int $sourceEntityId): self
    {
        $this->sourceEntityId = $sourceEntityId;

        return $this;
    }

    /**
     * Returns the arbitrary payload data to be passed to the PDF generator.
     */
    public function getPdfDocumentPayload(): array
    {
        return (array)$this->pdfDocumentPayload;
    }

    /**
     * Sets the arbitrary payload data to be passed to the PDF generator.
     */
    public function setPdfDocumentPayload(array $pdfDocumentPayload): self
    {
        $this->pdfDocumentPayload = $pdfDocumentPayload;

        return $this;
    }

    /**
     * Returns the PDF options preset name (e.g., default, default_a4, etc.).
     */
    public function getPdfOptionsPreset(): string
    {
        return $this->pdfOptionsPreset;
    }

    /**
     * Sets the PDF options preset name (e.g., default, default_a4, etc.).
     */
    public function setPdfOptionsPreset(string $pdfOptionsPreset): self
    {
        $this->pdfOptionsPreset = $pdfOptionsPreset;

        return $this;
    }

    /**
     * Returns the PDF document state (e.g., new, resolved, failed).
     */
    public function getPdfDocumentState(): string
    {
        return $this->pdfDocumentState;
    }

    /**
     * Sets the PDF document state (e.g., new, resolved, failed).
     */
    public function setPdfDocumentState(string $pdfDocumentState): self
    {
        $this->pdfDocumentState = $pdfDocumentState;

        return $this;
    }

    /**
     * Returns the mode for generating the PDF document (e.g., instant, deferred, async, deferred_async).
     */
    public function getPdfDocumentGenerationMode(): string
    {
        return $this->pdfDocumentGenerationMode;
    }

    /**
     * Sets the mode for generating the PDF document (e.g., instant, deferred, async, deferred_async).
     */
    public function setPdfDocumentGenerationMode($pdfDocumentGenerationMode): self
    {
        $this->pdfDocumentGenerationMode = $pdfDocumentGenerationMode;

        return $this;
    }

    /**
     * Returns the file entity representing the PDF document.
     */
    public function getPdfDocumentFile(): ?File
    {
        return $this->pdfDocumentFile;
    }

    /**
     * Sets the file entity representing the PDF document.
     */
    public function setPdfDocumentFile(?File $pdfDocumentFile): self
    {
        $this->pdfDocumentFile = $pdfDocumentFile;

        return $this;
    }
}
