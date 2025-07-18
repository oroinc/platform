<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Exception;

/**
 * Thrown when a required template is not provided for specific PDF document type.
 */
class PdfDocumentTemplateRequiredException extends PdfDocumentException
{
    public static function factory(string $pdfDocumentType, string $templateType): self
    {
        return new self(
            sprintf(
                'Template "%s" is required for the PDF document type "%s"',
                $templateType,
                $pdfDocumentType
            )
        );
    }
}
