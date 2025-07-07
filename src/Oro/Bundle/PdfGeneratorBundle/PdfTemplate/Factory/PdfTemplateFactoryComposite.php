<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplate\Factory;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Twig\TemplateWrapper;

/**
 * Creates a PDF template instance by delegating the template creation to applicable inner factory.
 */
class PdfTemplateFactoryComposite implements PdfTemplateFactoryInterface
{
    /**
     * @param iterable<PdfTemplateFactoryInterface> $innerPdfTemplateFactories
     */
    public function __construct(private iterable $innerPdfTemplateFactories)
    {
    }

    #[\Override]
    public function isApplicable(TemplateWrapper|string $template, array $context = []): bool
    {
        foreach ($this->innerPdfTemplateFactories as $pdfTemplateFactory) {
            if ($pdfTemplateFactory->isApplicable($template, $context)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function createPdfTemplate(TemplateWrapper|string $template, array $context = []): PdfTemplateInterface
    {
        foreach ($this->innerPdfTemplateFactories as $pdfTemplateFactory) {
            if ($pdfTemplateFactory->isApplicable($template, $context)) {
                return $pdfTemplateFactory->createPdfTemplate($template, $context);
            }
        }

        throw new \LogicException(sprintf(
            'No applicable PDF template factory found for template "%s"',
            is_string($template) ? $template : get_debug_type($template)
        ));
    }
}
