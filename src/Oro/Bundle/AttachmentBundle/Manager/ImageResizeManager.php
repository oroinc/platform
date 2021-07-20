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
    /** @var ResizedImageProviderInterface */
    private $resizedImageProvider;

    /** @var ResizedImagePathProviderInterface */
    private $resizedImagePathProvider;

    /** @var MediaCacheManagerRegistryInterface  */
    private $mediaCacheManagerRegistry;

    /** @var ImagineBinaryByFileContentFactoryInterface */
    private $imagineBinaryByFileContentFactory;

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

    /**
     * {@inheritdoc}
     */
    public function resize(File $file, int $width, int $height, bool $forceUpdate = false): ?BinaryInterface
    {
        $mediaCacheManager = $this->mediaCacheManagerRegistry->getManagerForFile($file);
        $storagePath = $this->resizedImagePathProvider->getPathForResizedImage($file, $width, $height);

        if (!$forceUpdate && $rawResizedImage = $mediaCacheManager->getFileContent($storagePath, false)) {
            $resizedImageBinary = $this->imagineBinaryByFileContentFactory->createImagineBinary($rawResizedImage);
        } else {
            $resizedImageBinary = $this->resizedImageProvider->getResizedImage($file, $width, $height);
            if (!$resizedImageBinary) {
                return null;
            }
            $rawResizedImage = $resizedImageBinary->getContent();
            $mediaCacheManager->writeToStorage($rawResizedImage, $storagePath);
        }

        return $resizedImageBinary;
    }

    /**
     * {@inheritdoc}
     */
    public function applyFilter(File $file, string $filterName, bool $forceUpdate = false): ?BinaryInterface
    {
        $mediaCacheManager = $this->mediaCacheManagerRegistry->getManagerForFile($file);
        $storagePath = $this->resizedImagePathProvider->getPathForFilteredImage($file, $filterName);

        if (!$forceUpdate && $rawResizedImage = $mediaCacheManager->getFileContent($storagePath, false)) {
            $resizedImageBinary = $this->imagineBinaryByFileContentFactory->createImagineBinary($rawResizedImage);
        } else {
            $resizedImageBinary = $this->resizedImageProvider->getFilteredImage($file, $filterName);
            if (!$resizedImageBinary) {
                return null;
            }
            $rawResizedImage = $resizedImageBinary->getContent();
            $mediaCacheManager->writeToStorage($rawResizedImage, $storagePath);
        }

        return $resizedImageBinary;
    }
}
