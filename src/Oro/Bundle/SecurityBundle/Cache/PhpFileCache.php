<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Doctrine\Common\Cache\PhpFileCache as BasePhpFileCache;

/**
 * The aim of this class is just modify an algorithm is used to generate file names
 * to avoid very long file names. We can do not use additional sha256 encoding used
 * in the original PhpFileCache class because $id passed to getFilename is quite unique itself.
 */
class PhpFileCache extends BasePhpFileCache
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
}
