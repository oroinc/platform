<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplate\Factory;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Twig\TemplateWrapper;

/**
 * Creates a PDF template instance.
 */
interface PdfTemplateFactoryInterface
{
    /**
     * @param TemplateWrapper|string $template TWIG template name or {@see TemplateWrapper}
     * @param array $context Variables to be passed to TWIG template.
     *
     * @return bool
     */
    public function isApplicable(TemplateWrapper|string $template, array $context = []): bool;

    /**
     * @param TemplateWrapper|string $template TWIG template name or {@see TemplateWrapper}
     * @param array $context Variables to be passed to TWIG template.
     *
     * @return PdfTemplateInterface
     */
    public function createPdfTemplate(TemplateWrapper|string $template, array $context = []): PdfTemplateInterface;
}
