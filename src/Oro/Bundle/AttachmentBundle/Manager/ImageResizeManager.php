<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;

/**
 * Manage full process of resizing and saving images.
 */
class ImageResizeManager implements ImageResizeManagerInterface
{
    private ResizedImageProviderInterface $resizedImageProvider;

    private ResizedImagePathProviderInterface $resizedImagePathProvider;

    private MediaCacheManagerRegistryInterface $mediaCacheManagerRegistry;

    private ImagineBinaryByFileContentFactoryInterface $imagineBinaryByFileContentFactory;

    public function __construct(
        ResizedImageProviderInterface $resizedImageProvider,
        ResizedImagePathProviderInterface $resizedImagePathProvider,
        MediaCacheManagerRegistryInterface $mediaCacheManagerRegistry,
        ImagineBinaryByFileContentFactoryInterface $imagineBinaryByFileContentFactory
    ) {
        $this->resizedImageProvider = $resizedImageProvider;
        $this->resizedImagePathProvider = $resizedImagePathProvider;
        $this->mediaCacheManagerRegistry = $mediaCacheManagerRegistry;
        $this->imagineBinaryByFileContentFactory = $imagineBinaryByFileContentFactory;
    }

    public function resize(
        File $file,
        int $width,
        int $height,
        string $format = '',
        bool $forceUpdate = false
    ): ?BinaryInterface {
        if ($file->getExternalUrl() !== null) {
            // Externally stored files cannot be managed.
            return null;
        }

        $mediaCacheManager = $this->mediaCacheManagerRegistry->getManagerForFile($file);
        $storagePath = $this->resizedImagePathProvider->getPathForResizedImage($file, $width, $height, $format);

        if (!$forceUpdate && $rawResizedImage = $mediaCacheManager->getFileContent($storagePath, false)) {
            $resizedImageBinary = $this->imagineBinaryByFileContentFactory->createImagineBinary($rawResizedImage);
        } else {
            $resizedImageBinary = $this->resizedImageProvider->getResizedImage($file, $width, $height, $format);
            if (!$resizedImageBinary) {
                return null;
            }
            $rawResizedImage = $resizedImageBinary->getContent();
            $mediaCacheManager->writeToStorage($rawResizedImage, $storagePath);
        }

        return $resizedImageBinary;
    }

    public function applyFilter(
        File $file,
        string $filterName,
        string $format = '',
        bool $forceUpdate = false
    ): ?BinaryInterface {
        if ($file->getExternalUrl() !== null) {
            // Externally stored files cannot be managed.
            return null;
        }

        $mediaCacheManager = $this->mediaCacheManagerRegistry->getManagerForFile($file);
        $storagePath = $this->resizedImagePathProvider->getPathForFilteredImage($file, $filterName, $format);

        if (!$forceUpdate && $rawResizedImage = $mediaCacheManager->getFileContent($storagePath, false)) {
            $resizedImageBinary = $this->imagineBinaryByFileContentFactory->createImagineBinary($rawResizedImage);
        } else {
            $resizedImageBinary = $this->resizedImageProvider->getFilteredImage($file, $filterName, $format);
            if (!$resizedImageBinary) {
                return null;
            }
            $rawResizedImage = $resizedImageBinary->getContent();
            $mediaCacheManager->writeToStorage($rawResizedImage, $storagePath);
        }

        return $resizedImageBinary;
    }
}
