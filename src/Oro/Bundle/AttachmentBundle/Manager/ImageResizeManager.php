<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\AttachmentBundle\Tools\LegacyMediaCachePathHelper;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;
use Symfony\Component\Lock\LockFactory;

/**
 * Manage full process of resizing and saving images.
 */
class ImageResizeManager implements ImageResizeManagerInterface
{
    private const LOCK_KEY_PREFIX = 'oro_attachment.media_cache_write';

    private const LOCK_TTL = 300;

    private ResizedImageProviderInterface $resizedImageProvider;

    private ResizedImagePathProviderInterface $resizedImagePathProvider;

    private MediaCacheManagerRegistryInterface $mediaCacheManagerRegistry;

    private ImagineBinaryByFileContentFactoryInterface $imagineBinaryByFileContentFactory;

    private ?LockFactory $lockFactory = null;

    public function __construct(
        ResizedImageProviderInterface $resizedImageProvider,
        ResizedImagePathProviderInterface $resizedImagePathProvider,
        MediaCacheManagerRegistryInterface $mediaCacheManagerRegistry,
        ImagineBinaryByFileContentFactoryInterface $imagineBinaryByFileContentFactory,
    ) {
        $this->resizedImageProvider = $resizedImageProvider;
        $this->resizedImagePathProvider = $resizedImagePathProvider;
        $this->mediaCacheManagerRegistry = $mediaCacheManagerRegistry;
        $this->imagineBinaryByFileContentFactory = $imagineBinaryByFileContentFactory;
    }

    public function setLockFactory(LockFactory $lockFactory): void
    {
        $this->lockFactory = $lockFactory;
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

        if (!$forceUpdate) {
            $storedImageBinary = $this->getStoredImage($mediaCacheManager, $storagePath);
            if (null !== $storedImageBinary) {
                return $storedImageBinary;
            }
        }

        $resizedImageBinary = $this->resizedImageProvider->getResizedImage($file, $width, $height, $format);
        if (!$resizedImageBinary) {
            return null;
        }

        return $this->storeResizedImage($mediaCacheManager, $storagePath, $resizedImageBinary, $forceUpdate);
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

        if (!$forceUpdate) {
            $storedImageBinary = $this->getStoredImage($mediaCacheManager, $storagePath);
            if (null !== $storedImageBinary) {
                return $storedImageBinary;
            }
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
        if ($this->lockFactory instanceof LockFactory) {
            $lock = $this->lockFactory->createLock(
                $this->getLockKey($mediaCacheManager, $storagePath),
                self::LOCK_TTL
            );
            $lock->acquire(true);
        }

        try {
            if (!$forceUpdate && $rawResizedImage = $mediaCacheManager->getFileContent($storagePath, false)) {
                return $this->imagineBinaryByFileContentFactory->createImagineBinary($rawResizedImage);
            }

            $mediaCacheManager->writeToStorage($resizedImageBinary->getContent(), $storagePath);
            if ($forceUpdate) {
                $this->deleteLegacyImage($mediaCacheManager, $storagePath);
            }

            return $resizedImageBinary;
        } finally {
            if (isset($lock)) {
                $lock->release();
            }
        }
    }

    /**
     * Returns an image that is already stored in the media cache.
     * An image stored under the legacy (percent-encoded) path is moved to the current path first,
     * so that the web server can serve it directly from the storage afterwards.
     */
    private function getStoredImage(GaufretteFileManager $mediaCacheManager, string $storagePath): ?BinaryInterface
    {
        $rawResizedImage = $mediaCacheManager->getFileContent($storagePath, false);
        if ($rawResizedImage) {
            return $this->imagineBinaryByFileContentFactory->createImagineBinary($rawResizedImage);
        }

        $legacyStoragePath = LegacyMediaCachePathHelper::getLegacyPath($storagePath);
        if ($legacyStoragePath === $storagePath) {
            return null;
        }

        $rawResizedImage = $mediaCacheManager->getFileContent($legacyStoragePath, false);
        if (!$rawResizedImage) {
            return null;
        }

        $this->moveLegacyImage($mediaCacheManager, $legacyStoragePath, $storagePath, $rawResizedImage);

        return $this->imagineBinaryByFileContentFactory->createImagineBinary($rawResizedImage);
    }

    private function moveLegacyImage(
        GaufretteFileManager $mediaCacheManager,
        string $legacyStoragePath,
        string $storagePath,
        string $rawResizedImage
    ): void {
        if ($this->lockFactory instanceof LockFactory) {
            $lock = $this->lockFactory->createLock(
                $this->getLockKey($mediaCacheManager, $storagePath),
                self::LOCK_TTL
            );
            $lock->acquire(true);
        }

        try {
            if (!$mediaCacheManager->hasFile($storagePath)) {
                $mediaCacheManager->writeToStorage($rawResizedImage, $storagePath);
            }
            $mediaCacheManager->deleteFile($legacyStoragePath);
        } finally {
            if (isset($lock)) {
                $lock->release();
            }
        }
    }

    private function deleteLegacyImage(GaufretteFileManager $mediaCacheManager, string $storagePath): void
    {
        $legacyStoragePath = LegacyMediaCachePathHelper::getLegacyPath($storagePath);
        if ($legacyStoragePath !== $storagePath) {
            $mediaCacheManager->deleteFile($legacyStoragePath);
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
