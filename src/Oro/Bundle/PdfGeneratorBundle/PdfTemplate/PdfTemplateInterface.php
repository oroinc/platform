<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplate;

use Twig\TemplateWrapper;

/**
 * Represents a PDF template.
 */
interface PdfTemplateInterface
{
    /**
     * @return TemplateWrapper|string TWIG template name.
     */
    public function getTemplate(): TemplateWrapper|string;

    /**
     * @return array<string, mixed> Variables to be passed to TWIG template.
     */
    public function getContext(): array;
}
