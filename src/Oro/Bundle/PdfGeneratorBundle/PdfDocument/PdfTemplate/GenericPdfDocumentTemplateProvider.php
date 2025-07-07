<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfTemplate;

use Twig\TemplateWrapper;

/**
 * Provides predefined static paths to Twig templates used for PDF documents.
 *
 * This implementation is intended for cases where template paths are static and not
 * depend on dynamic context such as themes or document types.
 */
class GenericPdfDocumentTemplateProvider implements PdfDocumentTemplateProviderInterface
{
    /**
     * @param string $pdfDocumentType The type of the PDF document (e.g., invoice_us_standard)
     * @param string $pdfContentTemplatePath Path to the Twig template used for the content section of the PDF
     * @param string|null $pdfHeaderTemplatePath Path to the Twig template used for the header section of the PDF
     * @param string|null $pdfFooterTemplatePath Path to the Twig template used for the footer section of the PDF
     */
    public function __construct(
        private readonly string $pdfDocumentType,
        private readonly string $pdfContentTemplatePath,
        private readonly ?string $pdfHeaderTemplatePath,
        private readonly ?string $pdfFooterTemplatePath
    ) {
    }

    #[\Override]
    public function getContentTemplate(string $pdfDocumentType): TemplateWrapper|string|null
    {
        return $this->pdfDocumentType === $pdfDocumentType ? $this->pdfContentTemplatePath : null;
    }

    #[\Override]
    public function getHeaderTemplate(string $pdfDocumentType): TemplateWrapper|string|null
    {
        return $this->pdfDocumentType === $pdfDocumentType ? $this->pdfHeaderTemplatePath : null;
    }

    #[\Override]
    public function getFooterTemplate(string $pdfDocumentType): TemplateWrapper|string|null
    {
        return $this->pdfDocumentType === $pdfDocumentType ? $this->pdfFooterTemplatePath : null;
    }
}
