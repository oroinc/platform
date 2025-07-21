<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;

/**
 * Finds a single PDF document by a source entity.
 */
class SinglePdfDocumentBySourceEntityProvider implements SinglePdfDocumentBySourceEntityProviderInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function findPdfDocument(
        object $sourceEntity,
        string $pdfDocumentName,
        string $pdfDocumentType
    ): ?AbstractPdfDocument {
        $sourceEntityId = $this->doctrineHelper->getSingleEntityIdentifier($sourceEntity);
        if ($sourceEntityId === null) {
            return null;
        }

        return $this->doctrineHelper
            ->getEntityRepositoryForClass(PdfDocument::class)
            ->findOneBy(
                [
                    'sourceEntityClass' => $this->doctrineHelper->getEntityClass($sourceEntity),
                    'sourceEntityId' => $sourceEntityId,
                    'pdfDocumentName' => $pdfDocumentName,
                    'pdfDocumentType' => $pdfDocumentType,
                ],
                ['id' => 'DESC']
            );
    }
}
