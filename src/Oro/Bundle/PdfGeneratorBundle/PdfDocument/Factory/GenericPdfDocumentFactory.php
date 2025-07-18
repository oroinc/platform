<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\AbstractPdfDocumentDemand;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentState;

/**
 * Creates a PDF document entity from a demand.
 */
class GenericPdfDocumentFactory implements PdfDocumentFactoryInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function createPdfDocument(AbstractPdfDocumentDemand $pdfDocumentDemand): PdfDocument
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setPdfDocumentState(PdfDocumentState::NEW);
        $pdfDocument->setPdfDocumentName($pdfDocumentDemand->getPdfDocumentName());
        $pdfDocument->setPdfDocumentType($pdfDocumentDemand->getPdfDocumentType());
        $pdfDocument->setPdfOptionsPreset($pdfDocumentDemand->getPdfOptionsPreset());

        $sourceEntityClass = $this->doctrineHelper->getEntityClass($pdfDocumentDemand->getSourceEntity());
        $pdfDocument->setSourceEntityClass($sourceEntityClass);

        $sourceEntityId = $this->doctrineHelper->getSingleEntityIdentifier($pdfDocumentDemand->getSourceEntity());
        $pdfDocument->setSourceEntityId($sourceEntityId);

        $pdfDocument->setPdfDocumentPayload($pdfDocumentDemand->getPdfDocumentPayload());

        return $pdfDocument;
    }
}
