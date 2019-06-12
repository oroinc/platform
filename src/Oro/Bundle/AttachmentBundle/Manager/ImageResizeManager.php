<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\AttachmentBundle\Tools\ThumbnailFactory;

/**
 * Manage full process of resizing and saving images.
 */
class ImageResizeManager implements ImageResizeManagerInterface
{
    /** @var ImageResizer */
    private $imageResizer;

    /** @var ThumbnailFactory */
    private $thumbnailFactory;

    /** @var ResizedImagePathProviderInterface */
    private $resizedImagePathProvider;

    /** @var MediaCacheManagerRegistryInterface  */
    private $mediaCacheManagerRegistry;

    /** @var ImagineBinaryByFileContentFactoryInterface */
    private $imagineBinaryByFileContentFactory;

    /** @var FileManager */
    private $fileManager;

    /**
     * @param ImageResizer $imageResizer
     * @param ThumbnailFactory $thumbnailFactory
     * @param ResizedImagePathProviderInterface $resizedImagePathProvider
     * @param MediaCacheManagerRegistryInterface $mediaCacheManagerRegistry
     * @param ImagineBinaryByFileContentFactoryInterface $imagineBinaryByFileContentFactory
     * @param FileManager $fileManager
     */
    public function __construct(
        ImageResizer $imageResizer,
        ThumbnailFactory $thumbnailFactory,
        ResizedImagePathProviderInterface $resizedImagePathProvider,
        MediaCacheManagerRegistryInterface $mediaCacheManagerRegistry,
        ImagineBinaryByFileContentFactoryInterface $imagineBinaryByFileContentFactory,
        FileManager $fileManager
    ) {
        $this->imageResizer = $imageResizer;
        $this->thumbnailFactory = $thumbnailFactory;
        $this->resizedImagePathProvider = $resizedImagePathProvider;
        $this->mediaCacheManagerRegistry = $mediaCacheManagerRegistry;
        $this->imagineBinaryByFileContentFactory = $imagineBinaryByFileContentFactory;
        $this->fileManager = $fileManager;
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
            $rawImageContent = $this->fileManager->getContent($file, false);
            if (!$rawImageContent) {
                return null;
            }

            $resizedImageBinary = $this->thumbnailFactory
                ->createThumbnail($rawImageContent, $width, $height)
                ->getBinary();
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
            $resizedImageBinary = $this->imageResizer->resizeImage($file, $filterName);
            if (!$resizedImageBinary) {
                return null;
            }
            $rawResizedImage = $resizedImageBinary->getContent();
            $mediaCacheManager->writeToStorage($rawResizedImage, $storagePath);
        }

        return $resizedImageBinary;
    }
}
