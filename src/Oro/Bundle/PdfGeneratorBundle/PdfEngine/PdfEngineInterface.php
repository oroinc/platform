<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfEngine;

use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsInterface;

/**
 * Generates a PDF represented by {@see PdfFileInterface} taking into account specified PDF options.
 */
interface PdfEngineInterface
{
    /**
     * Creates PDF taking into account specified PDF options.
     *
     * @param PdfOptionsInterface $pdfOptions
     *
     * @return PdfFileInterface
     */
    public function createPdfFile(PdfOptionsInterface $pdfOptions): PdfFileInterface;

    /**
     * @return string PDF engine name.
     */
    public static function getName(): string;
}
