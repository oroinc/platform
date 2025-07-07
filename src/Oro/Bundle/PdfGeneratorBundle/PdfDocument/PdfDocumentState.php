<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument;

/**
 * Contains states of a PDF document.
 */
class PdfDocumentState
{
    public const string NEW = 'new';
    public const string PENDING = 'pending';
    public const string DEFERRED = 'deferred';
    public const string IN_PROGRESS = 'in_progress';
    public const string RESOLVED = 'resolved';
    public const string FAILED = 'failed';
}
