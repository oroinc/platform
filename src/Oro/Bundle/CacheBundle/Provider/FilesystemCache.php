<?php

namespace Oro\Bundle\CacheBundle\Provider;

use Doctrine\Common\Cache\FilesystemCache as BaseFilesystemCache;

/**
 * The aims of this class:
 * 1) Modify an algorithm is used to generate file names to avoid very long file names.
 *    We can do not use additional sha256 encoding used in the original FilesystemCache class
 *    because $id passed to getFilename is quite unique itself.
 * 2) Provide a way to synchronize a cache between different processes.
 */
class FilesystemCache extends BaseFilesystemCache implements SyncCacheInterface
{
    use NamespaceVersionSyncTrait;
    use ShortFileNameGeneratorTrait;
}
