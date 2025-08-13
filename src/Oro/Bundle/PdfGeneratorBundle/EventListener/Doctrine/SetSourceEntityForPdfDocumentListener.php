<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PdfGeneratorBundle\Event\AfterPdfDocumentCreatedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Sets the source entity for a PDF document after it has been created.
 * This listener is responsible for associating the PDF document with its source entity.
 */
final class SetSourceEntityForPdfDocumentListener implements ResetInterface
{
    private \SplObjectStorage $splObjectStorage;

    public function __construct(private readonly DoctrineHelper $doctrineHelper)
    {
        $this->splObjectStorage = new \SplObjectStorage();
    }

    public function onAfterPdfDocumentCreated(AfterPdfDocumentCreatedEvent $afterPdfDocumentCreatedEvent): void
    {
        $pdfDocument = $afterPdfDocumentCreatedEvent->getPdfDocument();
        if ($pdfDocument->getSourceEntityId()) {
            // Skips the PDF document as it already has a source entity ID set.
            return;
        }

        $this->splObjectStorage->attach(
            $pdfDocument,
            $afterPdfDocumentCreatedEvent->getPdfDocumentDemand()->getSourceEntity()
        );
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        $doFlush = false;

        /** @var AbstractPdfDocument $pdfDocument */
        foreach ($this->splObjectStorage as $pdfDocument) {
            if (!$event->getObjectManager()->contains($pdfDocument)) {
                continue;
            }

            $sourceEntity = $this->splObjectStorage[$pdfDocument];

            $pdfDocument->setSourceEntityId($this->doctrineHelper->getSingleEntityIdentifier($sourceEntity));
            $doFlush = true;
        }

        $this->reset();

        if ($doFlush) {
            $entityManager = $event->getObjectManager();
            $entityManager->flush();
        }
    }

    public function onClear(): void
    {
        $this->reset();
    }

    #[\Override]
    public function reset(): void
    {
        $this->splObjectStorage = new \SplObjectStorage();
    }
}
