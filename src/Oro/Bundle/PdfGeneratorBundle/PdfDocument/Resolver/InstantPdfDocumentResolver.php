<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfGeneratorException;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Generator\PdfDocumentGeneratorInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentState;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\Factory\FileEntityFromPdfFileFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Mime\MimeTypes;

/**
 * Resolves the PDF document by generating a PDF file if applicable generator is found.
 */
class InstantPdfDocumentResolver implements PdfDocumentResolverInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private array $applicablePdfDocumentStates = [
        PdfDocumentState::PENDING,
        PdfDocumentState::DEFERRED,
        PdfDocumentState::FAILED,
    ];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PdfDocumentGeneratorInterface $pdfDocumentGenerator,
        private readonly FileEntityFromPdfFileFactoryInterface $fileFromPdfFileFactory
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * Sets the applicable PDF document states for which the resolver can be applied.
     */
    public function setApplicablePdfDocumentStates(array $applicablePdfDocumentStates): void
    {
        $this->applicablePdfDocumentStates = $applicablePdfDocumentStates;
    }

    #[\Override]
    public function resolvePdfDocument(AbstractPdfDocument $pdfDocument): void
    {
        if (!$this->isApplicable($pdfDocument)) {
            return;
        }

        $pdfDocument->setPdfDocumentState(PdfDocumentState::IN_PROGRESS);
        try {
            $pdfFile = $this->pdfDocumentGenerator->generatePdfFile($pdfDocument);

            $filename = $this->createFilename($pdfFile, $pdfDocument->getPdfDocumentName());
            $fileEntity = $this->fileFromPdfFileFactory->createFile($pdfFile, $filename);

            $pdfDocument->setPdfDocumentFile($fileEntity);
            $pdfDocument->setPdfDocumentState(PdfDocumentState::RESOLVED);

            $entityManager = $this->doctrine->getManagerForClass(File::class);
            $entityManager->persist($fileEntity);
        } catch (PdfGeneratorException $exception) {
            $pdfDocument->setPdfDocumentState(PdfDocumentState::FAILED);

            $this->logger->error(
                'PDF generation failed for PDF document {pdfDocumentId}: {message}',
                [
                    'pdfDocumentId' => $pdfDocument->getId(),
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]
            );
        }
    }

    #[\Override]
    public function isApplicable(AbstractPdfDocument $pdfDocument): bool
    {
        return in_array($pdfDocument->getPdfDocumentState(), $this->applicablePdfDocumentStates, true);
    }

    private function createFilename(PdfFileInterface $pdfFile, string $pdfDocumentName): string
    {
        $extension = MimeTypes::getDefault()->getExtensions($pdfFile->getMimeType())[0] ?? 'pdf';

        return sprintf('%s.%s', $pdfDocumentName, $extension);
    }
}
