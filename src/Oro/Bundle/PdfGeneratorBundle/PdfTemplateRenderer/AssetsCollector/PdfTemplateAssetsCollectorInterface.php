<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\AssetsCollector;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Collects the assets found during a PDF template rendering.
 */
interface PdfTemplateAssetsCollectorInterface extends ResetInterface
{
    /**
     * @return array<string,PdfTemplateAssetInterface> Collection of assets where key is an asset name and
     *  value is an instance of {@see PdfTemplateAssetInterface} instances.
     */
    public function getAssets(): array;

    /**
     * @param string $asset A URI or path of an image, stylesheet, font.
     *
     * @return string Name of the added asset.
     */
    public function addStaticAsset(string $asset): string;

    /**
     * @param string $data Asset contents.
     * @param string $name Asset name.
     *
     * @return string Name of the added asset.
     */
    public function addRawAsset(string $data, string $name): string;

    /**
     * Clears the collected assets.
     */
    #[\Override]
    public function reset(): void;
}
