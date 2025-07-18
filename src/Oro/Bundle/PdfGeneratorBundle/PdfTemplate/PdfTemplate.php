<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplate;

use Twig\TemplateWrapper;

/**
 * Represents a renderable PDF template.
 */
class PdfTemplate implements PdfTemplateInterface
{
    /**
     * @param TemplateWrapper|string $template TWIG template name or {@see TemplateWrapper}
     * @param array<string, mixed> $context Variables to be passed to TWIG template.
     */
    public function __construct(private TemplateWrapper|string $template, private array $context = [])
    {
    }

    #[\Override]
    public function getTemplate(): TemplateWrapper|string
    {
        return $this->template;
    }

    #[\Override]
    public function getContext(): array
    {
        return $this->context;
    }
}
