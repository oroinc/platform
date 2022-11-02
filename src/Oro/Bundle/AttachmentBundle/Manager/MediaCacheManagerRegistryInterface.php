<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;

/**
 * Interface for class which can return public or protected media cache file manager for the given file.
 */
interface MediaCacheManagerRegistryInterface
{
    /**
     * Returns public or protected media cache manager for the given file.
     */
    public function getManagerForFile(File $file): GaufretteFileManager;
}
