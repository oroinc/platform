<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Gotenberg;

use Gotenberg\Stream;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;

/**
 * Creates Gotenberg assets represented by a {@see Stream}.
 */
class GotenbergAssetFactory
{
    /**
     * Creates Gotenberg assets from the specified PDF template asset and its inner assets.
     *
     * @param PdfTemplateAssetInterface $pdfTemplateAsset
     *
     * @return array<string,Stream> Gotenberg assets  indexed by an asset name. If $pdfTemplateAsset does not contain
     *  inner assets, then the returned array will have only 1 element.
     */
    public function createFromPdfTemplateAsset(PdfTemplateAssetInterface $pdfTemplateAsset): array
    {
        $gotenbergAssets = [];
        $assetName = $pdfTemplateAsset->getName();
        $gotenbergAssets[$assetName] = new Stream($assetName, $pdfTemplateAsset->getStream());

        foreach ($this->flattenInnerAssets($pdfTemplateAsset) as $innerAsset) {
            $innerAssetName = $innerAsset->getName();
            if (!isset($gotenbergAssets[$innerAssetName])) {
                $gotenbergAssets[$innerAssetName] = new Stream($innerAssetName, $innerAsset->getStream());
            }
        }

        return $gotenbergAssets;
    }

    /**
     * @param PdfTemplateAssetInterface $pdfTemplateAsset
     *
     * @return array<PdfTemplateAssetInterface>
     */
    private function flattenInnerAssets(PdfTemplateAssetInterface $pdfTemplateAsset): array
    {
        $innerAssets = [];
        foreach ($pdfTemplateAsset->getInnerAssets() as $innerAsset) {
            $innerAssets[] = [$innerAsset->getName() => $innerAsset];
            if ($innerAsset->getInnerAssets()) {
                $innerAssets[] = $this->flattenInnerAssets($innerAsset);
            }
        }

        return array_merge(...$innerAssets);
    }
}
