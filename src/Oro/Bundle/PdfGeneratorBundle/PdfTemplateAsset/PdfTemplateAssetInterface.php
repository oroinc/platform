<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset;

use Psr\Http\Message\StreamInterface;

/**
 * Represents a PDF template asset.
 */
interface PdfTemplateAssetInterface
{
    /**
     * @return string Asset name, i.e. as it referenced in the template.
     */
    public function getName(): string;

    /**
     * @return string|null Asset filepath, i.e. where it is located in filesystem.
     *  May be null if an asset is represented only by raw data.
     */
    public function getFilepath(): ?string;

    /**
     * @return StreamInterface Asset contents represented by {@see StreamInterface}.
     */
    public function getStream(): StreamInterface;

    /**
     * @return array<string,PdfTemplateAssetInterface> Array of inner PDF template assets,
     *  i.e. referenced from the current one.
     */
    public function getInnerAssets(): array;
}
