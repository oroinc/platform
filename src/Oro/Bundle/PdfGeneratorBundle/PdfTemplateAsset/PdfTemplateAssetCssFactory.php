<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset;

use GuzzleHttp\Psr7\Utils;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfTemplateAssetException;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * Creates a {@see PdfTemplateAsset}.
 */
class PdfTemplateAssetCssFactory implements PdfTemplateAssetFactoryInterface
{
    public function __construct(
        private PdfTemplateAssetFactoryInterface $basicPdfTemplateAssetFactory,
        private LazyObjectInterface|PdfTemplateAssetFactoryInterface $pdfTemplateAssetFactory
    ) {
    }

    /**
     * @throws PdfTemplateAssetException
     */
    #[\Override]
    public function createFromPath(
        string $filepath,
        ?string $name = null,
        array $innerAssets = []
    ): PdfTemplateAssetInterface {
        $basicPdfTemplateAsset = $this->basicPdfTemplateAssetFactory->createFromPath($filepath, $name, $innerAssets);

        return $this->doCreateFromBasicPdfTemplateAsset($basicPdfTemplateAsset);
    }

    /**
     * @throws PdfTemplateAssetException
     */
    #[\Override]
    public function createFromRawData(string $data, string $name, array $innerAssets = []): PdfTemplateAssetInterface
    {
        $basicPdfTemplateAsset = $this->basicPdfTemplateAssetFactory->createFromRawData($data, $name, $innerAssets);

        return $this->doCreateFromBasicPdfTemplateAsset($basicPdfTemplateAsset);
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
        $basicPdfTemplateAsset = $this->basicPdfTemplateAssetFactory->createFromStream($stream, $name, $innerAssets);

        return $this->doCreateFromBasicPdfTemplateAsset($basicPdfTemplateAsset);
    }

    #[\Override]
    public function isApplicable(
        ?string $name,
        ?string $filepath,
        ?StreamInterface $stream,
        array $innerAssets = []
    ): bool {
        return Path::hasExtension($this->cleanName($name ?? $filepath), 'css', true);
    }

    /**
     * @throws PdfTemplateAssetException
     */
    private function doCreateFromBasicPdfTemplateAsset(
        PdfTemplateAssetInterface $basicPdfTemplateAsset
    ): PdfTemplateAssetInterface {
        $stream = $basicPdfTemplateAsset->getStream();
        if (!$stream->isReadable() || !$stream->isSeekable()) {
            throw new PdfTemplateAssetException(
                sprintf(
                    'Impossible to extract inner assets from the non-readable/non-seekable PDF template asset "%s"',
                    $basicPdfTemplateAsset->getName(),
                ),
                0
            );
        }

        $baseDir = Path::getDirectory($basicPdfTemplateAsset->getFilepath() ?? '');
        $innerAssets = [];
        $newStream = null;
        $read = 0;
        while (!$stream->eof()) {
            $line = $newLine = Utils::readLine($stream);
            $innerAssetsUri = $this->extractFromString($line);

            if ($innerAssetsUri) {
                foreach ($innerAssetsUri as $uri) {
                    if (str_starts_with($uri, 'data:')) {
                        continue;
                    }

                    $innerAssetName = $this->createAssetName($uri);
                    $newLine = str_replace($uri, $innerAssetName, $newLine);

                    $innerAsset = $this->pdfTemplateAssetFactory
                        ->createFromPath($this->cleanUri($uri, $baseDir), $this->cleanName($innerAssetName));
                    $innerAssets[$innerAsset->getName()] = $innerAsset;
                }

                if ($newStream === null) {
                    $newStream = Utils::streamFor(Utils::tryFopen('php://memory', 'rb+'));

                    $stream->rewind();
                    Utils::copyToStream($stream, $newStream, $read);
                    $stream->seek($read + strlen($line));
                }
            }

            $newStream?->write($newLine);
            $read += strlen($line);
        }

        $stream->rewind();

        if ($newStream !== null) {
            return $this->basicPdfTemplateAssetFactory->createFromStream(
                $newStream,
                $basicPdfTemplateAsset->getName(),
                array_merge($innerAssets, $basicPdfTemplateAsset->getInnerAssets())
            );
        }

        return $basicPdfTemplateAsset;
    }

    /**
     * @param string $string
     *
     * @return array<string>
     */
    private function extractFromString(string $string): array
    {
        if (!str_contains($string, 'url(')) {
            return [];
        }

        preg_match_all('/url\((?:"|\'|)(.*?)(?:"|\'|)\)/i', $string, $match);

        return $match[1] ?? [];
    }

    private function createAssetName(string $assetUri): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '__', $assetUri);
    }

    /**
     * Transforms a relative URI to absolute.
     * Cleans the URI by removing query parameters and fragments.
     */
    private function cleanUri(string $uri, string $assetBaseDir): string
    {
        if ($assetBaseDir !== '' && Path::isLocal($uri)) {
            $uri = Path::makeAbsolute($uri, $assetBaseDir);
        }

        return strtok($uri, '?#');
    }

    /**
     * Cleans the asset name by removing query parameters and fragments.
     */
    private function cleanName(string $name): string
    {
        return strtok($name, '?#');
    }
}
