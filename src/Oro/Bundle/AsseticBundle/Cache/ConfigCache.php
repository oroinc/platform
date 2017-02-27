<?php

namespace Oro\Bundle\AsseticBundle\Cache;

use Assetic\Cache\ConfigCache as BaseCache;

class ConfigCache extends BaseCache
{
    /**
     * @var string
     */
    protected $dir;

    /**
     * Construct.
     *
     * @param string $dir The cache directory
     */
    public function __construct($dir)
    {
        parent::__construct($dir);

        $this->dir = $dir;
    }

    /**
     * {@inheritdoc}
     *
     * Do not save empty value into caches
     * @see \Assetic\Cache\ConfigCache::has
     */
    public function set($resource, $value)
    {
        $path = $this->getSourcePath($resource);

        if (!$value) {
            if (file_exists($path)) {
                @unlink($path);
            }

            return;
        }

        parent::set($resource, $value);
    }

    /**
     * {@inheritdoc}
     *
     * If cache file not exists - return empty config
     * @see \Assetic\Factory\Loader\CachedFormulaLoader::load
     */
    public function get($resource)
    {
        $path = $this->getSourcePath($resource);

        if (!file_exists($path)) {
            return [];
        }

        return parent::get($resource);
    }

    /**
     * {@inheritdoc}
     *
     * If cache file not exists - in's to fresh anymore
     * @see \Assetic\Factory\Resource\FileResource::isFresh
     */
    public function getTimestamp($resource)
    {
        $path = $this->getSourcePath($resource);

        if (!file_exists($path)) {
            return 0;
        }

        return parent::getTimestamp($resource);
    }

    /**
     * Returns the path where the file corresponding to the supplied cache key can be included from.
     *
     * @param string $resource A cache key
     *
     * @return string A file path
     */
    private function getSourcePath($resource)
    {
        $key = md5($resource);

        return $this->dir.'/'.$key[0].'/'.$key.'.php';
    }
}
