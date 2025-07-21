<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfTemplate;

use Twig\TemplateWrapper;

/**
 * Composite provider for PDF document templates.
 * Delegates the retrieval of a template to inner providers.
 */
class PdfDocumentTemplateProviderComposite implements PdfDocumentTemplateProviderInterface
{
    /**
     * @param iterable<PdfDocumentTemplateProviderInterface> $innerProviders
     */
    public function __construct(private readonly iterable $innerProviders)
    {
    }

    #[\Override]
    public function getContentTemplate(string $pdfDocumentType): TemplateWrapper|string|null
    {
        foreach ($this->innerProviders as $innerProvider) {
            $template = $innerProvider->getContentTemplate($pdfDocumentType);
            if ($template !== null) {
                return $template;
            }
        }

        return null;
    }

    #[\Override]
    public function getHeaderTemplate(string $pdfDocumentType): TemplateWrapper|string|null
    {
        foreach ($this->innerProviders as $innerProvider) {
            $template = $innerProvider->getHeaderTemplate($pdfDocumentType);
            if ($template !== null) {
                return $template;
            }
        }

        return null;
    }

    #[\Override]
    public function getFooterTemplate(string $pdfDocumentType): TemplateWrapper|string|null
    {
        foreach ($this->innerProviders as $innerProvider) {
            $template = $innerProvider->getFooterTemplate($pdfDocumentType);
            if ($template !== null) {
                return $template;
            }
        }

        return null;
    }
}
