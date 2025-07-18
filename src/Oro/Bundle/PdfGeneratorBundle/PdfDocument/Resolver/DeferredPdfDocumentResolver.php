<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Resolver;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentState;

/**
 * PDF document resolver that defers the generation of a PDF file.
 */
class DeferredPdfDocumentResolver implements PdfDocumentResolverInterface
{
    #[\Override]
    public function resolvePdfDocument(AbstractPdfDocument $pdfDocument): void
    {
        if (!$this->isApplicable($pdfDocument)) {
            return;
        }

        // The PDF document is not generated immediately, but rather deferred for later processing.
        $pdfDocument->setPdfDocumentState(PdfDocumentState::DEFERRED);
    }

    #[\Override]
    public function isApplicable(AbstractPdfDocument $pdfDocument): bool
    {
        return $pdfDocument->getPdfDocumentState() === PdfDocumentState::PENDING;
    }
}
