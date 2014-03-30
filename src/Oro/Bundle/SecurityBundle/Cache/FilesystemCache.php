<?php

namespace Oro\Bundle\SecurityBundle\Cache;

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
    /**
     * {@inheritdoc}
     */
    protected function getFilename($id)
    {
        $namespace = $this->getNamespace();
        if ($namespace && strpos($id, $namespace) === 0) {
            $id = substr($id, strlen($namespace));
        }
        $id = preg_replace('@[\\\/:"*?<>|]+@', '', $id);

        return $this->directory . DIRECTORY_SEPARATOR
        . ($namespace ? preg_replace('@[\\\/:"*?<>|]+@', '', $namespace) . DIRECTORY_SEPARATOR : '')
        . $id . $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function sync()
    {
        // set $this->namespaceVersion to NULL; it will force to load latest cache version from the file system
        $this->setNamespace($this->getNamespace());
    }
}
