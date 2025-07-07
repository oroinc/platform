<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\AssetsCollector;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;

/**
 * Collects the assets found during a PDF template rendering.
 */
class PdfTemplateAssetsCollector implements PdfTemplateAssetsCollectorInterface
{
    /** @var array<string,PdfTemplateAssetInterface> */
    private array $assets = [];

    public function __construct(private PdfTemplateAssetFactoryInterface $pdfTemplateAssetFactory)
    {
    }

    #[\Override]
    public function getAssets(): array
    {
        return $this->assets;
    }

    #[\Override]
    public function addStaticAsset(string $asset): string
    {
        $pdfTemplateAsset = $this->pdfTemplateAssetFactory->createFromPath($asset);
        $this->assets[$pdfTemplateAsset->getName()] = $pdfTemplateAsset;

        return $pdfTemplateAsset->getName();
    }

    #[\Override]
    public function addRawAsset(string $data, string $name): string
    {
        $pdfTemplateAsset = $this->pdfTemplateAssetFactory->createFromRawData($data, $name);
        $this->assets[$name] = $pdfTemplateAsset;

        return $name;
    }

    #[\Override]
    public function reset(): void
    {
        $this->assets = [];
    }
}
