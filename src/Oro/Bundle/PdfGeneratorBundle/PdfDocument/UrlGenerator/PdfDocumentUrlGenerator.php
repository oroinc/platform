<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\UrlGenerator;

use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Generates URL for the PDF document.
 */
class PdfDocumentUrlGenerator implements PdfDocumentUrlGeneratorInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $routeName
    ) {
    }

    #[\Override]
    public function generateUrl(
        AbstractPdfDocument $pdfDocument,
        string $fileAction = FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->urlGenerator->generate(
            $this->routeName,
            ['uuid' => $pdfDocument->getUuid(), 'fileAction' => $fileAction],
            $referenceType
        );
    }
}
