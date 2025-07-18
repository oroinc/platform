<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument;

use Doctrine\Common\Collections\Collection;

/**
 * Interface for an entity holding a collection of PDF documents.
 */
interface PdfDocumentContainerInterface
{
    /**
     * Adds a PDF document to the collection.
     */
    public function addPdfDocument(AbstractPdfDocument $pdfDocument): self;

    /**
     * Removes a PDF document from the collection.
     */
    public function removePdfDocument(AbstractPdfDocument $pdfDocument): self;

    /**
     * Returns the collection of PDF documents.
     *
     * @return Collection<AbstractPdfDocument>
     */
    public function getPdfDocuments(): Collection;
}
