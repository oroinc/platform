<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\LegacyMediaCachePathHelper;

/**
 * Provides filenames of all resized/filtered images for a specific File entity.
 * The legacy (percent-encoded) filenames are included as well, so that the images stored
 * under them are removed together with the current ones.
 */
class ImageFileNamesProvider implements FileNamesProviderInterface
{
    private FilterConfiguration $filterConfiguration;

    private ResizedImagePathProviderInterface $imagePathProvider;

    public function __construct(
        FilterConfiguration $filterConfiguration,
        ResizedImagePathProviderInterface $imagePathProvider
    ) {
        $this->filterConfiguration = $filterConfiguration;
        $this->imagePathProvider = $imagePathProvider;
    }

    #[\Override]
    public function getFileNames(File $file): array
    {
        $fileNames = [];
        $dimensions = $this->filterConfiguration->all();
        foreach ($dimensions as $dimension => $dimensionConfig) {
            $fileNames[] = $this->normalizeFileName(
                $this->imagePathProvider->getPathForFilteredImage($file, $dimension)
            );
            $fileNames[] = $this->normalizeFileName(
                $this->imagePathProvider->getPathForFilteredImage($file, $dimension, 'webp')
            );
        }
        $fileNames[] = $this->normalizeFileName($this->imagePathProvider->getPathForResizedImage($file, 1, 1));
        $fileNames[] = $this->normalizeFileName($this->imagePathProvider->getPathForResizedImage($file, 1, 1, 'webp'));

        return array_values(array_unique($this->addLegacyFileNames($fileNames)));
    }

    private function normalizeFileName(string $fileName): string
    {
        return ltrim($fileName, '/');
    }

    /**
     * @param string[] $fileNames
     *
     * @return string[]
     */
    private function addLegacyFileNames(array $fileNames): array
    {
        foreach ($fileNames as $fileName) {
            $legacyFileName = LegacyMediaCachePathHelper::getLegacyPath($fileName);
            if ($legacyFileName !== $fileName) {
                $fileNames[] = $legacyFileName;
            }
        }

        return $fileNames;
    }
}
