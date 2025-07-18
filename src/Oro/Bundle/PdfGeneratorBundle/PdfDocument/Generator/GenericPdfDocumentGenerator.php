<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Generator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentGeneratedEvent;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfDocumentTemplateRequiredException;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfTemplate\PdfDocumentTemplateProviderInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\Factory\PdfTemplateFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Generic PDF document generator that generates a PDF file based on the provided PDF document.
 */
class GenericPdfDocumentGenerator implements PdfDocumentGeneratorInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PdfBuilderFactoryInterface $pdfBuilderFactory,
        private readonly PdfDocumentTemplateProviderInterface $pdfDocumentTemplateProvider,
        private readonly PdfTemplateFactoryInterface $pdfTemplateFactory,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    #[\Override]
    public function isApplicable(AbstractPdfDocument $pdfDocument): bool
    {
        return true;
    }

    /**
     * @throws PdfDocumentTemplateRequiredException
     */
    #[\Override]
    public function generatePdfFile(AbstractPdfDocument $pdfDocument): PdfFileInterface
    {
        $pdfDocumentPayload = $this->getDocumentPayload($pdfDocument);
        $pdfBuilder = $this->pdfBuilderFactory->createPdfBuilder($pdfDocument->getPdfOptionsPreset());

        $pdfDocumentGenerateBefore = new BeforePdfDocumentGeneratedEvent(
            $pdfBuilder,
            $pdfDocument,
            $pdfDocumentPayload
        );
        $this->eventDispatcher->dispatch($pdfDocumentGenerateBefore);

        $pdfDocumentPayload = $pdfDocumentGenerateBefore->getPdfDocumentPayload();
        $pdfDocumentType = $pdfDocument->getPdfDocumentType();

        $headerTwigTemplate = $this->pdfDocumentTemplateProvider->getHeaderTemplate($pdfDocumentType);
        if ($headerTwigTemplate !== null) {
            $pdfBuilder->header($this->pdfTemplateFactory->createPdfTemplate($headerTwigTemplate, $pdfDocumentPayload));
        }

        $footerTwigTemplate = $this->pdfDocumentTemplateProvider->getFooterTemplate($pdfDocumentType);
        if ($footerTwigTemplate !== null) {
            $pdfBuilder->footer($this->pdfTemplateFactory->createPdfTemplate($footerTwigTemplate, $pdfDocumentPayload));
        }

        $contentTwigTemplate = $this->pdfDocumentTemplateProvider->getContentTemplate($pdfDocumentType);
        if ($contentTwigTemplate === null) {
            throw PdfDocumentTemplateRequiredException::factory($pdfDocumentType, 'content');
        }

        return $pdfBuilder
            ->content($this->pdfTemplateFactory->createPdfTemplate($contentTwigTemplate, $pdfDocumentPayload))
            ->createPdfFile();
    }

    private function getDocumentPayload(AbstractPdfDocument $pdfDocument): array
    {
        $sourceEntity = $this->doctrine
            ->getRepository($pdfDocument->getSourceEntityClass())
            ->find($pdfDocument->getSourceEntityId());

        return ['entity' => $sourceEntity, ...$pdfDocument->getPdfDocumentPayload()];
    }
}
