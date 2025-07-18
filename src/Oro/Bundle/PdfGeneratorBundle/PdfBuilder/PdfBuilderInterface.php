<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfBuilder;

use Oro\Bundle\PdfGeneratorBundle\Model\Size;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;

/**
 * Builds a PDF file.
 */
interface PdfBuilderInterface
{
    /**
     * Sets the page main content template.
     */
    public function content(PdfTemplateInterface $pdfTemplate): self;

    /**
     * Sets the page header content template.
     */
    public function header(PdfTemplateInterface $pdfTemplate): self;

    /**
     * Sets the page footer content template.
     */
    public function footer(PdfTemplateInterface $pdfTemplate): self;

    /**
     * Adds an allowed asset represented either by {@see PdfTemplateAssetInterface} or asset filepath.
     */
    public function asset(PdfTemplateAssetInterface|string $asset): self;

    /**
     * Sets the page size.
     *
     * @param Size|string|int|float $width
     *  Example: 8.5in or 8.5
     * @param Size|string|int|float $height
     *  Example: 11in or 11
     *
     * Examples of page size (width x height), in inches:
     *      Letter - 8.5 x 11
     *      Legal - 8.5 x 14
     *      Tabloid - 11 x 17
     *      Ledger - 17 x 11
     *      A0 - 33.1 x 46.8
     *      A1 - 23.4 x 33.1
     *      A2 - 16.54 x 23.4
     *      A3 - 11.7 x 16.54
     *      A4 - 8.27 x 11.7
     *      A5 - 5.83 x 8.27
     *      A6 - 4.13 x 5.83
     */
    public function pageSize(Size|string|int|float $width, Size|string|int|float $height): self;

    /**
     * Sets the page top margin.
     *
     * @param Size|string|int|float $marginTop
     *  Example: 2in or 2 or 2.1
     * @param Size|string|int|float $marginBottom
     *  Example: 2in or 2 or 2.1
     * @param Size|string|int|float $marginLeft
     *  Example: 2in or 2 or 2.1
     * @param Size|string|int|float $marginRight
     *  Example: 2in or 2 or 2.1
     */
    public function margins(
        Size|string|int|float $marginTop,
        Size|string|int|float $marginBottom,
        Size|string|int|float $marginLeft,
        Size|string|int|float $marginRight
    ): self;

    /**
     * Sets the page orientation to landscape.
     */
    public function landscape(bool $landscape): self;

    /**
     * Sets the scale of the page rendering (i.e., 1.0 is 100%).
     */
    public function scale(float $scale): self;

    /**
     * Adds a custom option to pass to a PDF engine.
     * Allows to pass an option not defined in a PDF options configurator.
     */
    public function customOption(string $name, mixed $value = null): self;

    /**
     * Sets a defined option.
     * Allows to pass an option defined in a PDF options configurator, i.e. to override the previous value.
     */
    public function option(string $name, mixed $value): self;

    /**
     * Creates PDF and returns the result as an instance of {@see PdfFileInterface}.
     */
    public function createPdfFile(): PdfFileInterface;
}
