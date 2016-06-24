<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Provides an algorithm to generate short name of cache file.
 *
 * This trait can be used in a cache implementation bases on \Doctrine\Common\Cache\FileCache
 *
 * @method string getDirectory
 * @method string getExtension
 * @method string getNamespace
 */
trait ShortFileNameGeneratorTrait
{
    /**
     * Gets a file name
     *
     * @param string $id
     * @return string
     */
    protected function getFilename($id)
    {
        $namespace = $this->getNamespace();
        if ($namespace && strpos($id, $namespace) === 0) {
            $id = substr($id, strlen($namespace));
        }
        $id = $this->removeSpecialChars($id);

        return
            $this->getDirectory()
            . DIRECTORY_SEPARATOR
            . ($namespace ? $this->removeSpecialChars($namespace) . DIRECTORY_SEPARATOR : '')
            . substr(hash('sha256', $id), 0, 2)
            . DIRECTORY_SEPARATOR
            . $id . $this->getExtension();
    }

    /**
     * Removes special characters like \/:? and others from the given string
     *
     * @param string $str
     * @return string
     */
    protected function removeSpecialChars($str)
    {
        return preg_replace('@[\\\/:"*?<>|]+@', '', $str);
    }
}
