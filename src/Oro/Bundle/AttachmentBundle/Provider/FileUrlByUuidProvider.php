<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Exception\FileNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provide file urls by file UUID
 */
class FileUrlByUuidProvider
{
    /** @var FileUrlProviderInterface */
    private $fileUrlProvider;

    /** @var CacheProvider */
    private $cache;

    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param FileUrlProviderInterface $fileUrlProvider
     * @param CacheProvider $cache
     * @param ManagerRegistry $registry
     */
    public function __construct(
        FileUrlProviderInterface $fileUrlProvider,
        CacheProvider $cache,
        ManagerRegistry $registry
    ) {
        $this->fileUrlProvider = $fileUrlProvider;
        $this->cache = $cache;
        $this->registry = $registry;
    }

    /**
     * Get file URL.
     *
     * @param string $uuid
     * @param string $action
     * @param int $referenceType
     *
     * @return string
     *
     * @throws FileNotFoundException
     */
    public function getFileUrl(
        string $uuid,
        string $action = FileUrlProviderInterface::FILE_ACTION_GET,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->fileUrlProvider->getFileUrl(
            $this->findFileByUuid($uuid),
            $action,
            $referenceType
        );
    }

    /**
     * Get resized image URL.
     *
     * @param string $uuid
     * @param int $width
     * @param int $height
     * @param int $referenceType
     *
     * @return string
     *
     * @throws FileNotFoundException
     */
    public function getResizedImageUrl(
        string $uuid,
        int $width,
        int $height,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->fileUrlProvider->getResizedImageUrl(
            $this->findFileByUuid($uuid),
            $width,
            $height,
            $referenceType
        );
    }

    /**
     * Get URL to the image with applied liip imagine filter.
     *
     * @param string $uuid
     * @param string $filterName
     * @param int $referenceType
     *
     * @return string
     *
     * @throws FileNotFoundException
     */
    public function getFilteredImageUrl(
        string $uuid,
        string $filterName,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->fileUrlProvider->getFilteredImageUrl(
            $this->findFileByUuid($uuid),
            $filterName,
            $referenceType
        );
    }

    /**
     * Find file by uuid with caching all files from entity related to that file
     *
     * @param string $uuid
     *
     * @return File
     *
     * @throws FileNotFoundException
     */
    private function findFileByUuid(string $uuid): File
    {
        $file = $this->cache->fetch($uuid);
        if ($file) {
            return $file;
        }

        $fileRepository = $this->registry->getRepository(File::class);

        $files = $fileRepository->findAllForEntityByOneUuid($uuid);
        if ($files) {
            $this->cache->saveMultiple($files);

            if (isset($files[$uuid])) {
                return $files[$uuid];
            }
        }

        throw new FileNotFoundException(sprintf('File with UUID "%s" not found', $uuid));
    }
}
