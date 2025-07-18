<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Utils;
use Oro\Bundle\DistributionBundle\Provider\PublicDirectoryProvider;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Filesystem\Path;

/**
 * Creates a {@see PdfTemplateAsset}.
 */
class PdfTemplateAssetBasicFactory implements PdfTemplateAssetFactoryInterface
{
    private ?string $publicDirectory = null;

    public function __construct(private PublicDirectoryProvider $publicDirectoryProvider)
    {
    }

    #[\Override]
    public function createFromPath(
        string $filepath,
        string|null $name = null,
        array $innerAssets = []
    ): PdfTemplateAssetInterface {
        if (Path::isLocal($filepath)) {
            $filepath = parse_url($filepath, PHP_URL_PATH);
            $name ??= str_replace(DIRECTORY_SEPARATOR, '__', $filepath);

            if (!Path::isAbsolute($filepath) || !is_readable($filepath)) {
                $this->publicDirectory ??= $this->publicDirectoryProvider->getPublicDirectory();

                $filepath = Path::join($this->publicDirectory, $filepath);
            }
        } else {
            $name ??= str_replace(DIRECTORY_SEPARATOR, '__', parse_url($filepath, PHP_URL_PATH));
        }

        return new PdfTemplateAsset($name, $filepath, new LazyOpenStream($filepath, 'r'), $innerAssets);
    }

    #[\Override]
    public function createFromRawData(string $data, string $name, array $innerAssets = []): PdfTemplateAssetInterface
    {
        $stream = Utils::streamFor(Utils::tryFopen('php://memory', 'rb+'));
        $stream->write($data);

        return new PdfTemplateAsset($name, null, $stream, $innerAssets);
    }

    #[\Override]
    public function createFromStream(
        StreamInterface $stream,
        string $name,
        array $innerAssets = []
    ): PdfTemplateAssetInterface {
        return new PdfTemplateAsset($name, null, $stream, $innerAssets);
    }

    #[\Override]
    public function isApplicable(
        ?string $name,
        ?string $filepath,
        ?StreamInterface $stream,
        array $innerAssets = []
    ): bool {
        return true;
    }
}
