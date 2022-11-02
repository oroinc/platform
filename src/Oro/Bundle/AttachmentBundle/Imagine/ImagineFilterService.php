<?php

namespace Oro\Bundle\AttachmentBundle\Imagine;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;

/**
 * Provides url for the image with applied LiipImagine filter.
 */
class ImagineFilterService
{
    private DataManager $dataManager;

    private CacheManager $cacheManager;

    private FilterConfiguration $filterConfiguration;

    private ResizedImageProviderInterface $resizedImageProvider;

    private FilenameExtensionHelper $filenameExtensionHelper;

    public function __construct(
        DataManager $dataManager,
        CacheManager $cacheManager,
        FilterConfiguration $filterConfiguration,
        ResizedImageProviderInterface $resizedImageProvider,
        FilenameExtensionHelper $filenameExtensionHelper
    ) {
        $this->dataManager = $dataManager;
        $this->cacheManager = $cacheManager;
        $this->filterConfiguration = $filterConfiguration;
        $this->resizedImageProvider = $resizedImageProvider;
        $this->filenameExtensionHelper = $filenameExtensionHelper;
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
        if (!$format) {
            $format = $this->filterConfiguration->get($filterName)['format'] ?? '';
        }

        $targetPath = $this->filenameExtensionHelper->addExtension($path, $format);

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
}
