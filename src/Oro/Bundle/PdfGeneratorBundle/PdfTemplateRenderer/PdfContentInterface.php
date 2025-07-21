<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;

/**
 * Represents a PDF content.
 */
interface PdfContentInterface
{
    /**
     * @return string PDF content.
     */
    public function getContent(): string;

    /**
     * @return array<string,PdfTemplateAssetInterface> Collection of assets where key is an asset name and
     *  value is an instance of {@see PdfTemplateAssetInterface} instances.
     */
    public function getAssets(): array;
}
