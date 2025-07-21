<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfOptions;

use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;

/**
 * Creates PDF options taking into account PDF engine and PDF options preset.
 */
interface PdfOptionsFactoryInterface
{
    /**
     * @param string $pdfEngineName
     * @param string $pdfOptionsPreset PDF options preset name (e.g., default, default_a4, etc.).
     *
     * @return PdfOptionsInterface
     */
    public function createPdfOptions(
        string $pdfEngineName,
        string $pdfOptionsPreset = PdfOptionsPreset::DEFAULT
    ): PdfOptionsInterface;
}
