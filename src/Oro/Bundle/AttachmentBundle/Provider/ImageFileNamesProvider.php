<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Provides filenames of all resized/filtered images for a specific File entity.
 */
class ImageFileNamesProvider implements FileNamesProviderInterface
{
    /** @var FilterConfiguration */
    private $filterConfiguration;

    /** @var ResizedImagePathProviderInterface */
    private $imagePathProvider;

    public function __construct(
        FilterConfiguration $filterConfiguration,
        ResizedImagePathProviderInterface $imagePathProvider
    ) {
        $this->filterConfiguration = $filterConfiguration;
        $this->imagePathProvider = $imagePathProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getFileNames(File $file): array
    {
        $fileNames = [];
        $dimensions = $this->filterConfiguration->all();
        foreach ($dimensions as $dimension => $dimensionConfig) {
            $fileNames[] = $this->normalizeFileName(
                $this->imagePathProvider->getPathForFilteredImage($file, $dimension)
            );
        }
        $fileNames[] = $this->normalizeFileName(
            $this->imagePathProvider->getPathForResizedImage($file, 1, 1)
        );

        return array_values(array_unique($fileNames));
    }

    private function normalizeFileName(string $fileName): string
    {
        return ltrim($fileName, '/');
    }
}
