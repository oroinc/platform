<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfBuilder;

use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;

/**
 * Creates a PDF builder for the specified PDF options preset.
 */
interface PdfBuilderFactoryInterface
{
    /**
     * @param string $pdfOptionsPreset PDF options preset name (e.g., default, default_a4, etc.).
     */
    public function createPdfBuilder(string $pdfOptionsPreset = PdfOptionsPreset::DEFAULT): PdfBuilderInterface;
}
