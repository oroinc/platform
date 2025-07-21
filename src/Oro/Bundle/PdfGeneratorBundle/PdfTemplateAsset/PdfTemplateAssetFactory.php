<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset;

use Oro\Bundle\PdfGeneratorBundle\Exception\PdfTemplateAssetException;
use Psr\Http\Message\StreamInterface;

/**
 * Creates an instance of {@see PdfTemplateAssetInterface} by delegating a call to inner factories.
 */
class PdfTemplateAssetFactory implements PdfTemplateAssetFactoryInterface
{
    /**
     * @param iterable<PdfTemplateAssetFactoryInterface> $innerFactories
     */
    public function __construct(private iterable $innerFactories)
    {
    }

    /**
     * @throws PdfTemplateAssetException
     */
    #[\Override]
    public function createFromPath(
        string $filepath,
        string|null $name = null,
        array $innerAssets = []
    ): PdfTemplateAssetInterface {
        foreach ($this->innerFactories as $innerFactory) {
            if ($innerFactory->isApplicable($name, $filepath, null, $innerAssets)) {
                return $innerFactory->createFromPath($filepath, $name, $innerAssets);
            }
        }

        throw new PdfTemplateAssetException('Failed to create a PDF template asset: no applicable factory found.');
    }

    /**
     * @throws PdfTemplateAssetException
     */
    #[\Override]
    public function createFromRawData(string $data, string $name, array $innerAssets = []): PdfTemplateAssetInterface
    {
        foreach ($this->innerFactories as $innerFactory) {
            if ($innerFactory->isApplicable($name, null, null, $innerAssets)) {
                return $innerFactory->createFromRawData($data, $name, $innerAssets);
            }
        }

        throw new PdfTemplateAssetException('Failed to create a PDF template asset: no applicable factory found.');
    }

    /**
     * @throws PdfTemplateAssetException
     */
    #[\Override]
    public function createFromStream(
        StreamInterface $stream,
        string $name,
        array $innerAssets = []
    ): PdfTemplateAssetInterface {
        foreach ($this->innerFactories as $innerFactory) {
            if ($innerFactory->isApplicable($name, null, null, $innerAssets)) {
                return $innerFactory->createFromStream($stream, $name, $innerAssets);
            }
        }

        throw new PdfTemplateAssetException('Failed to create a PDF template asset: no applicable factory found.');
    }

    #[\Override]
    public function isApplicable(
        ?string $name,
        ?string $filepath,
        ?StreamInterface $stream,
        array $innerAssets = []
    ): bool {
        return array_any(
            $this->innerFactories,
            static fn ($innerFactory) => $innerFactory->isApplicable($name, null, null, $innerAssets)
        );
    }
}
