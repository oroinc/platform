<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset;

use Psr\Http\Message\StreamInterface;

/**
 * Creates an instance of {@see PdfTemplateAssetInterface}.
 */
interface PdfTemplateAssetFactoryInterface
{
    /**
     * @param string $filepath A URI or path of an image, stylesheet, font, i.e. where it is located in filesystem.
     * @param string|null $name Asset name, i.e. as it referenced in the template.
     *  If not specified explicitly - takes the basename from filepath.
     * @param array<PdfTemplateAssetInterface> $innerAssets List of inner PDF template assets,
     *  i.e. referenced from the current one.
     */
    public function createFromPath(
        string $filepath,
        string|null $name = null,
        array $innerAssets = []
    ): PdfTemplateAssetInterface;

    /**
     * @param string $data Asset raw data.
     * @param string $name Asset name, i.e. as it referenced in the template.
     * @param array<PdfTemplateAssetInterface> $innerAssets List of inner PDF template assets,
     *  i.e. referenced from the current one.
     */
    public function createFromRawData(
        string $data,
        string $name,
        array $innerAssets = []
    ): PdfTemplateAssetInterface;

    /**
     * @param StreamInterface $stream Asset represented as an instance of {@see StreamInterface}.
     * @param string $name Asset name, i.e. as it referenced in the template.
     * @param array<PdfTemplateAssetInterface> $innerAssets List of inner PDF template assets,
     *  i.e. referenced from the current one.
     */
    public function createFromStream(
        StreamInterface $stream,
        string $name,
        array $innerAssets = []
    ): PdfTemplateAssetInterface;

    /**
     * @param string|null $name
     * @param string|null $filepath
     * @param StreamInterface|null $stream
     * @param array $innerAssets
     *
     * @return bool True if the factory can create an instance of {@see PdfTemplateAssetInterface}.
     */
    public function isApplicable(
        ?string $name,
        ?string $filepath,
        ?StreamInterface $stream,
        array $innerAssets = []
    ): bool;
}
