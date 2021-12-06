<?php

namespace Oro\Bundle\AttachmentBundle\Imagine;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\FilenameSanitizer;

/**
 * Provides url for the image with applied LiipImagine filter.
 */
class ImagineFilterService
{
    private DataManager $dataManager;

    private CacheManager $cacheManager;

    private ResizedImageProviderInterface $resizedImageProvider;

    public function __construct(
        DataManager $dataManager,
        CacheManager $cacheManager,
        ResizedImageProviderInterface $resizedImageProvider
    ) {
        $this->dataManager = $dataManager;
        $this->cacheManager = $cacheManager;
        $this->resizedImageProvider = $resizedImageProvider;
    }

    /**
     * Provides url for the image with applied LiipImagine filter.
     * Converts the resulting image to $format if needed.
     */
    public function getUrlOfFilteredImage(
        string $path,
        string $filterName,
        string $format = '',
        string $resolver = null
    ): string {
        $targetPath = $this->getTargetPath($path, $format);

        if (!$this->cacheManager->isStored($targetPath, $filterName, $resolver)) {
            $filteredImageBinary = $this->getFilteredImageBinary($path, $filterName, $format);
            $this->cacheManager->store($filteredImageBinary, $targetPath, $filterName, $resolver);
        }

        return $this->cacheManager->resolve($targetPath, $filterName, $resolver);
    }

    private function getFilteredImageBinary(
        string $path,
        string $filterName,
        string $format
    ): BinaryInterface {
        $originalImageBinary = $this->dataManager->find($filterName, $path);

        return $this->resizedImageProvider
            ->getFilteredImageByContent($originalImageBinary->getContent(), $filterName, $format);
    }

    private function getTargetPath(string $path, string $format): string
    {
        $format = FilenameSanitizer::sanitizeFilename($format);
        if ($format && pathinfo($path, PATHINFO_EXTENSION) !== $format) {
            $targetPath = $path . '.' . $format;
        } else {
            $targetPath = $path;
        }

        return $targetPath;
    }
}
