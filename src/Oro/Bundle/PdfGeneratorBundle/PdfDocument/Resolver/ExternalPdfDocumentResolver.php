<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentState;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * PDF document resolver that takes the file entity from the PDF document payload and sets it as the PDF document file.
 */
class ExternalPdfDocumentResolver implements PdfDocumentResolverInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ManagerRegistry $doctrine
    ) {
        $this->logger = new NullLogger();
    }

    #[\Override]
    public function resolvePdfDocument(AbstractPdfDocument $pdfDocument): void
    {
        if (!$this->isApplicable($pdfDocument)) {
            return;
        }

        // Extracts the file entity from the PDF document payload.
        $pdfDocumentPayload = $pdfDocument->getPdfDocumentPayload();
        $fileEntity = $pdfDocumentPayload['file'] ?? null;
        // Excludes the file entity from the payload to avoid serializing it when PDF document gets persisted.
        unset($pdfDocumentPayload['file']);
        // Sets the remaining payload back to the PDF document.
        $pdfDocument->setPdfDocumentPayload($pdfDocumentPayload);

        if ($fileEntity === null) {
            $this->logger->notice(
                'External PDF document resolver skips the PDF document {pdfDocumentId}: file is missing from payload',
                [
                    'pdfDocumentId' => $pdfDocument->getId(),
                    'pdfDocumentPayload' => $pdfDocumentPayload,
                ]
            );

            return;
        }

        if (!$fileEntity instanceof File) {
            $pdfDocument->setPdfDocumentState(PdfDocumentState::FAILED);

            $this->logger->error(
                'External PDF document resolver failed for the PDF document {pdfDocumentId}: '
                . 'file is expected to be an instance of "{expectedFile}", got "{actualFile}"',
                [
                    'pdfDocumentId' => $pdfDocument->getId(),
                    'expectedFile' => File::class,
                    'actualFile' => get_debug_type($fileEntity),
                    'pdfDocumentPayload' => $pdfDocumentPayload,
                ]
            );

            return;
        }

        $pdfDocument->setPdfDocumentFile($fileEntity);
        $pdfDocument->setPdfDocumentState(PdfDocumentState::RESOLVED);

        $entityManager = $this->doctrine->getManagerForClass(File::class);
        $entityManager->persist($fileEntity);
    }

    #[\Override]
    public function isApplicable(AbstractPdfDocument $pdfDocument): bool
    {
        return $pdfDocument->getPdfDocumentState() !== PdfDocumentState::RESOLVED;
    }
}
