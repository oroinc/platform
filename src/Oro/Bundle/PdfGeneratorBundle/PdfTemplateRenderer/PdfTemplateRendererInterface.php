<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;

/**
 * Renders a TWIG template during PDF generation.
 */
interface PdfTemplateRendererInterface
{
    /**
     * @return PdfContentInterface Rendered PDF template.
     */
    public function render(PdfTemplateInterface $pdfTemplate): PdfContentInterface;
}
