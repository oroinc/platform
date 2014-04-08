<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Provides an algorithm to generate short name of cache file.
 *
 * This trait can be used in a cache implementation bases on \Doctrine\Common\Cache\FileCache
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

        return $this->directory . DIRECTORY_SEPARATOR
        . ($namespace ? $this->removeSpecialChars($namespace) . DIRECTORY_SEPARATOR : '')
        . $id . $this->extension;
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
