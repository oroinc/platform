<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplate\Factory;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplate;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Twig\TemplateWrapper;

/**
 * Default factory to create a PDF template.
 */
class DefaultPdfTemplateFactory implements PdfTemplateFactoryInterface
{
    #[\Override]
    public function isApplicable(TemplateWrapper|string $template, array $context = []): bool
    {
        return true;
    }

    #[\Override]
    public function createPdfTemplate(TemplateWrapper|string $template, array $context = []): PdfTemplateInterface
    {
        return new PdfTemplate($template, $context);
    }
}
