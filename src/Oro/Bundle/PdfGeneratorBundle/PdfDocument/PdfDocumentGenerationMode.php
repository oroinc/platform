<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument;

/**
 * Contains modes of PDF generation.
 * These modes determine how and when the PDF documents are generated.
 */
class PdfDocumentGenerationMode
{
    // Instant generation mode: PDF documents are generated immediately.
    public const string INSTANT = 'instant';
    // Deferred generation mode: PDF documents are generated when explicitly requested, e.g. via controller.
    public const string DEFERRED = 'deferred';
}
