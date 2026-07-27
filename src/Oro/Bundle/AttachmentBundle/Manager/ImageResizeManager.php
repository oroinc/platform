<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;
use Symfony\Component\Lock\LockFactory;

/**
 * Manage full process of resizing and saving images.
 */
class ImageResizeManager implements ImageResizeManagerInterface
{
    private const string LOCK_KEY_PREFIX = 'oro_attachment.media_cache_write';

    private const int LOCK_TTL = 300;

    private ResizedImageProviderInterface $resizedImageProvider;

    private ResizedImagePathProviderInterface $resizedImagePathProvider;

    private MediaCacheManagerRegistryInterface $mediaCacheManagerRegistry;

    private ImagineBinaryByFileContentFactoryInterface $imagineBinaryByFileContentFactory;

    private LockFactory $lockFactory;

    public function __construct(
        ResizedImageProviderInterface $resizedImageProvider,
        ResizedImagePathProviderInterface $resizedImagePathProvider,
        MediaCacheManagerRegistryInterface $mediaCacheManagerRegistry,
        ImagineBinaryByFileContentFactoryInterface $imagineBinaryByFileContentFactory,
        LockFactory $lockFactory
    ) {
        $this->resizedImageProvider = $resizedImageProvider;
        $this->resizedImagePathProvider = $resizedImagePathProvider;
        $this->mediaCacheManagerRegistry = $mediaCacheManagerRegistry;
        $this->imagineBinaryByFileContentFactory = $imagineBinaryByFileContentFactory;
        $this->lockFactory = $lockFactory;
    }

    #[\Override]
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
            return $this->imagineBinaryByFileContentFactory->createImagineBinary($rawResizedImage);
        }

        $resizedImageBinary = $this->resizedImageProvider->getResizedImage($file, $width, $height, $format);
        if (!$resizedImageBinary) {
            return null;
        }

        return $this->storeResizedImage($mediaCacheManager, $storagePath, $resizedImageBinary, $forceUpdate);
    }

    #[\Override]
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
            return $this->imagineBinaryByFileContentFactory->createImagineBinary($rawResizedImage);
        }

        $resizedImageBinary = $this->resizedImageProvider->getFilteredImage($file, $filterName, $format);
        if (!$resizedImageBinary) {
            return null;
        }

        return $this->storeResizedImage($mediaCacheManager, $storagePath, $resizedImageBinary, $forceUpdate);
    }

    private function storeResizedImage(
        GaufretteFileManager $mediaCacheManager,
        string $storagePath,
        BinaryInterface $resizedImageBinary,
        bool $forceUpdate
    ): BinaryInterface {
        $lock = $this->lockFactory->createLock(
            $this->getLockKey($mediaCacheManager, $storagePath),
            self::LOCK_TTL
        );
        $lock->acquire(true);

        try {
            if (!$forceUpdate && $rawResizedImage = $mediaCacheManager->getFileContent($storagePath, false)) {
                return $this->imagineBinaryByFileContentFactory->createImagineBinary($rawResizedImage);
            }

            $mediaCacheManager->writeToStorage($resizedImageBinary->getContent(), $storagePath);

            return $resizedImageBinary;
        } finally {
            $lock->release();
        }
    }

    private function getLockKey(GaufretteFileManager $mediaCacheManager, string $storagePath): string
    {
        return sprintf(
            '%s:%s',
            self::LOCK_KEY_PREFIX,
            $mediaCacheManager->getFilePathWithoutProtocol($storagePath)
        );
    }
}
