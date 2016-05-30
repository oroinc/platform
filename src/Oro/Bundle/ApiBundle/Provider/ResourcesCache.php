<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ResourcesCache
{
    const RESOURCES_KEY_PREFIX            = 'resources_';
    const ACCESSIBLE_RESOURCES_KEY_PREFIX = 'accessible_';

    /** @var CacheProvider */
    protected $cache;

    /**
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Fetches a list of entity classes accessible through Data API from the cache.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return string[]|null The list of entity classes accessible through Data API or NULL if it is not cached yet
     */
    public function getAccessibleResources($version, RequestType $requestType)
    {
        $resources = $this->cache->fetch(
            self::ACCESSIBLE_RESOURCES_KEY_PREFIX . $this->getCacheKeyIndex($version, $requestType)
        );

        return false !== $resources
            ? $resources
            : null;
    }

    /**
     * Fetches all Data API resources from the cache.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return ApiResource[]|null The list of Data API resources or NULL if it is not cached yet
     */
    public function getResources($version, RequestType $requestType)
    {
        $resources = $this->cache->fetch(
            self::RESOURCES_KEY_PREFIX . $this->getCacheKeyIndex($version, $requestType)
        );
        if (false === $resources) {
            return null;
        }

        $result = [];
        foreach ($resources as $entityClass => $cachedData) {
            $result[] = $this->unserializeApiResource($entityClass, $cachedData);
        }

        return $result;
    }

    /**
     * Puts Data API resources into the cache.
     *
     * @param string        $version     The Data API version
     * @param RequestType   $requestType The request type, for example "rest", "soap", etc.
     * @param ApiResource[] $resources   The list of Data API resources
     */
    public function save($version, RequestType $requestType, array $resources)
    {
        $allResources = [];
        $accessibleResources = [];
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            $allResources[$entityClass] = $this->serializeApiResource($resource);
            if (!in_array('get', $resource->getExcludedActions(), true)) {
                $accessibleResources[] = $entityClass;
            }
        }

        $keyIndex = $this->getCacheKeyIndex($version, $requestType);
        $this->cache->save(self::RESOURCES_KEY_PREFIX . $keyIndex, $allResources);
        $this->cache->save(self::ACCESSIBLE_RESOURCES_KEY_PREFIX . $keyIndex, $accessibleResources);
    }

    /**
     * Deletes all Data API resources from the cache.
     */
    public function clear()
    {
        $this->cache->deleteAll();
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return string
     */
    protected function getCacheKeyIndex($version, RequestType $requestType)
    {
        return $version . (string)$requestType;
    }

    /**
     * @param string $entityClass
     * @param array  $cachedData
     *
     * @return ApiResource
     */
    protected function unserializeApiResource($entityClass, array $cachedData)
    {
        $resource = new ApiResource($entityClass);
        $resource->setExcludedActions($cachedData[0]);

        return $resource;
    }

    /**
     * @param ApiResource $resource
     *
     * @return array
     */
    protected function serializeApiResource(ApiResource $resource)
    {
        return [
            $resource->getExcludedActions()
        ];
    }
}
