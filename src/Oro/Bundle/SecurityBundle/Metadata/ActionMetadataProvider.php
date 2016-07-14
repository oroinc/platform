<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Doctrine\Common\Cache\CacheProvider;

class ActionMetadataProvider
{
    const CACHE_KEY = 'data';

    /**
     * @var AclAnnotationProvider
     */
    protected $annotationProvider;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var array
     *         key = action name
     *         value = ActionMetadata
     */
    protected $localCache;

    /**
     * Constructor
     *
     * @param AclAnnotationProvider $annotationProvider
     * @param CacheProvider|null    $cache
     */
    public function __construct(
        AclAnnotationProvider $annotationProvider,
        CacheProvider $cache = null
    ) {
        $this->annotationProvider = $annotationProvider;
        $this->cache = $cache;
    }

    /**
     * Checks whether an action with the given name is defined.
     *
     * @param  string $actionName The entity class name
     * @return bool
     */
    public function isKnownAction($actionName)
    {
        $this->ensureMetadataLoaded();

        return isset($this->localCache[$actionName]);
    }

    /**
     * Gets metadata for all actions.
     *
     * @return ActionMetadata[]
     */
    public function getActions()
    {
        $this->ensureMetadataLoaded();

        return array_values($this->localCache);
    }

    /**
     * Warms up the cache
     */
    public function warmUpCache()
    {
        $this->loadMetadata();
    }

    /**
     * Clears the cache
     */
    public function clearCache()
    {
        if ($this->cache) {
            $this->cache->delete(self::CACHE_KEY);
        }
        $this->localCache = null;
    }

    /**
     * Makes sure that metadata are loaded and cached
     */
    protected function ensureMetadataLoaded()
    {
        if ($this->localCache === null) {
            $data = null;
            if ($this->cache) {
                $data = $this->cache->fetch(self::CACHE_KEY);
            }
            if ($data) {
                $this->localCache = $data;
            } else {
                $this->loadMetadata();
            }
        }
    }

    /**
     * Loads metadata and save them in cache
     */
    protected function loadMetadata()
    {
        $data = array();
        foreach ($this->annotationProvider->getAnnotations('action') as $annotation) {
            $data[$annotation->getId()] = new ActionMetadata(
                $annotation->getId(),
                $annotation->getGroup(),
                $annotation->getLabel(),
                $annotation->getDescription(),
                $annotation->getCategory()
            );
        }

        if ($this->cache) {
            $this->cache->save(self::CACHE_KEY, $data);
        }

        $this->localCache = $data;
    }
}
