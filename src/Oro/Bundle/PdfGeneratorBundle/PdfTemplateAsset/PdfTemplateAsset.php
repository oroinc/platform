<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset;

use Psr\Http\Message\StreamInterface;

/**
 * Represents a PDF template asset.
 */
class PdfTemplateAsset implements PdfTemplateAssetInterface
{
    /**
     * @param string $name Asset name, i.e. as it referenced in the template.
     * @param string|null $filepath Asset filepath, if any, i.e. where it is located in filesystem.
     * @param StreamInterface $stream Asset contents represented by {@see StreamInterface}.
     * @param array<PdfTemplateAssetInterface> $innerAssets List of inner PDF template assets,
     *  i.e. referenced from the current one.
     */
    public function __construct(
        private string $name,
        private ?string $filepath,
        private StreamInterface $stream,
        private array $innerAssets = []
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    #[\Override]
    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    #[\Override]
    public function getInnerAssets(): array
    {
        return $this->innerAssets;
    }
}
