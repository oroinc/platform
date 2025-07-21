<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfTemplate;

use Twig\TemplateWrapper;

/**
 * Provides paths to Twig templates for PDF documents.
 */
interface PdfDocumentTemplateProviderInterface
{
    /**
     * Retrieves the Twig template for content section.
     *
     * @param string $pdfDocumentType The type of the PDF document (e.g., invoice_us_standard).
     */
    public function getContentTemplate(string $pdfDocumentType): TemplateWrapper|string|null;

    /**
     * Retrieves the Twig template for header section.
     *
     * @param string $pdfDocumentType The type of the PDF document (e.g., invoice_us_standard).
     */
    public function getHeaderTemplate(string $pdfDocumentType): TemplateWrapper|string|null;

    /**
     * Retrieves the Twig template for footer section.
     *
     * @param string $pdfDocumentType The type of the PDF document (e.g., invoice_us_standard).
     */
    public function getFooterTemplate(string $pdfDocumentType): TemplateWrapper|string|null;
}
