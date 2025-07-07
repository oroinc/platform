<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Operator;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PdfGeneratorBundle\Event\AfterPdfDocumentCreatedEvent;
use Oro\Bundle\PdfGeneratorBundle\Event\AfterPdfDocumentResolvedEvent;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentCreatedEvent;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentResolvedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\AbstractPdfDocumentDemand;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Factory\PdfDocumentFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentState;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Resolver\PdfDocumentResolverInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * The generic entry point for operating with PDF documents.
 */
class GenericPdfDocumentOperator implements PdfDocumentOperatorInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PdfDocumentFactoryInterface $pdfDocumentFactory,
        private readonly PdfDocumentResolverInterface $pdfDocumentResolver,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $pdfDocumentGenerationMode
    ) {
    }

    #[\Override]
    public function isResolvedPdfDocument(AbstractPdfDocument $pdfDocument): bool
    {
        return !$this->pdfDocumentResolver->isApplicable($pdfDocument);
    }

    #[\Override]
    public function resolvePdfDocument(AbstractPdfDocument $pdfDocument): void
    {
        $this->eventDispatcher->dispatch(
            new BeforePdfDocumentResolvedEvent($pdfDocument, $this->pdfDocumentGenerationMode)
        );

        if (!$this->pdfDocumentResolver->isApplicable($pdfDocument)) {
            return;
        }

        $this->pdfDocumentResolver->resolvePdfDocument($pdfDocument);

        $this->eventDispatcher->dispatch(
            new AfterPdfDocumentResolvedEvent($pdfDocument, $this->pdfDocumentGenerationMode)
        );
    }

    #[\Override]
    public function createPdfDocument(AbstractPdfDocumentDemand $pdfDocumentDemand): AbstractPdfDocument
    {
        $this->eventDispatcher->dispatch(
            new BeforePdfDocumentCreatedEvent($pdfDocumentDemand, $this->pdfDocumentGenerationMode)
        );

        $pdfDocument = $this->pdfDocumentFactory->createPdfDocument($pdfDocumentDemand);

        $this->eventDispatcher->dispatch(
            new AfterPdfDocumentCreatedEvent($pdfDocument, $this->pdfDocumentGenerationMode)
        );

        /** @var EntityManagerInterface|null $entityManager */
        $entityManager = $this->doctrine->getManagerForClass(ClassUtils::getClass($pdfDocument));
        $entityManager?->persist($pdfDocument);

        $pdfDocument->setPdfDocumentGenerationMode($this->pdfDocumentGenerationMode);
        $pdfDocument->setPdfDocumentState(PdfDocumentState::PENDING);

        $this->resolvePdfDocument($pdfDocument);

        return $pdfDocument;
    }

    #[\Override]
    public function updatePdfDocument(AbstractPdfDocument $pdfDocument): void
    {
        $pdfDocument->setPdfDocumentGenerationMode($this->pdfDocumentGenerationMode);
        $pdfDocument->setPdfDocumentState(PdfDocumentState::PENDING);

        $this->resolvePdfDocument($pdfDocument);
    }

    #[\Override]
    public function deletePdfDocument(AbstractPdfDocument $pdfDocument): void
    {
        /** @var EntityManagerInterface|null $entityManager */
        $entityManager = $this->doctrine->getManagerForClass(ClassUtils::getClass($pdfDocument));
        $entityManager?->remove($pdfDocument);
    }
}
