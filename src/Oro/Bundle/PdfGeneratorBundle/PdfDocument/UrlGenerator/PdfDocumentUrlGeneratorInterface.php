<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\UrlGenerator;

use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Interface for generating URLs for PDF documents.
 */
interface PdfDocumentUrlGeneratorInterface
{
    /**
     * Generates a URL for the given PDF document.
     *
     * @param AbstractPdfDocument $pdfDocument The PDF document for which the URL is generated.
     * @param string $fileAction The action to perform on file. Expects constants from {@see FileUrlProviderInterface}.
     * @param int $referenceType The type of URL to generate. Expects constants from {@see UrlGeneratorInterface}.
     *
     * @return string The generated URL.
     */
    public function generateUrl(
        AbstractPdfDocument $pdfDocument,
        string $fileAction = FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string;
}
