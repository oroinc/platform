<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Oro\Bundle\AttachmentBundle\Acl\FileAccessControlChecker;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;

/**
 * Default implementation of media cache manager registry.
 */
class MediaCacheManagerRegistry implements MediaCacheManagerRegistryInterface
{
    /** @var FileAccessControlChecker */
    private $fileAccessControlChecker;

    /** @var GaufretteFileManager */
    private $publicMediaCacheManager;

    /** @var GaufretteFileManager */
    private $protectedMediaCacheManager;

    public function __construct(
        FileAccessControlChecker $fileAccessControlChecker,
        GaufretteFileManager $publicMediaCacheManager,
        GaufretteFileManager $protectedMediaCacheManager
    ) {
        $this->fileAccessControlChecker = $fileAccessControlChecker;
        $this->publicMediaCacheManager = $publicMediaCacheManager;
        $this->protectedMediaCacheManager = $protectedMediaCacheManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerForFile(File $file): GaufretteFileManager
    {
        if ($this->fileAccessControlChecker->isCoveredByAcl($file)) {
            return $this->protectedMediaCacheManager;
        }

        return $this->publicMediaCacheManager;
    }
}
