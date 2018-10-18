<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Provides access to Data API resources and sub-resources related cache.
 */
class ResourcesCache
{
    private const RESOURCES_KEY_PREFIX            = 'resources_';
    private const SUBRESOURCE_KEY_PREFIX          = 'subresource_';
    private const ACCESSIBLE_RESOURCES_KEY_PREFIX = 'accessible_';
    private const RESOURCES_WITHOUT_ID_KEY_PREFIX = 'resources_wid_';
    private const EXCLUDED_ACTIONS_KEY_PREFIX     = 'excluded_actions_';

    /** @var CacheProvider */
    private $cache;

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
     * @return array|null [entity class => accessible flag] or NULL if the list is not cached yet
     */
    public function getAccessibleResources(string $version, RequestType $requestType): ?array
    {
        $resources = $this->cache->fetch(
            self::ACCESSIBLE_RESOURCES_KEY_PREFIX . $this->getCacheKeyIndex($version, $requestType)
        );

        if (false === $resources) {
            return null;
        }

        return $resources;
    }

    /**
     * Fetches an information about excluded actions from the cache.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return array|null [entity class => [action, ...]] or NULL if the list is not cached yet
     */
    public function getExcludedActions(string $version, RequestType $requestType): ?array
    {
        $excludedActions = $this->cache->fetch(
            self::EXCLUDED_ACTIONS_KEY_PREFIX . $this->getCacheKeyIndex($version, $requestType)
        );

        if (false === $excludedActions) {
            return null;
        }

        return $excludedActions;
    }

    /**
     * Fetches all Data API resources from the cache.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return ApiResource[]|null The list of Data API resources or NULL if it is not cached yet
     */
    public function getResources(string $version, RequestType $requestType): ?array
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
     * Fetches an entity sub-resources from the cache.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return ApiResourceSubresources|null The list of sub-resources or NULL if it is not cached yet
     */
    public function getSubresources(
        string $entityClass,
        string $version,
        RequestType $requestType
    ): ?ApiResourceSubresources {
        $cachedData = $this->cache->fetch(
            self::SUBRESOURCE_KEY_PREFIX . $this->getCacheKeyIndex($version, $requestType) . $entityClass
        );

        if (false === $cachedData) {
            return null;
        }

        return $this->unserializeApiResourceSubresources($entityClass, $cachedData);
    }

    /**
     * Fetches a list of entity classes for API resources that do not have an identifier.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return string[] The list of class names or NULL if it is not cached yet
     */
    public function getResourcesWithoutIdentifier(string $version, RequestType $requestType): ?array
    {
        $resources = $this->cache->fetch(
            self::RESOURCES_WITHOUT_ID_KEY_PREFIX . $this->getCacheKeyIndex($version, $requestType)
        );

        if (false === $resources) {
            return null;
        }

        return $resources;
    }

    /**
     * Puts Data API resources into the cache.
     *
     * @param string        $version             The Data API version
     * @param RequestType   $requestType         The request type, for example "rest", "soap", etc.
     * @param ApiResource[] $resources           The list of Data API resources
     * @param array         $accessibleResources The resources accessible through Data API
     * @param array         $excludedActions     The actions excluded from Data API
     */
    public function saveResources(
        string $version,
        RequestType $requestType,
        array $resources,
        array $accessibleResources,
        array $excludedActions
    ): void {
        $allResources = [];
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            $allResources[$entityClass] = $this->serializeApiResource($resource);
        }

        $keyIndex = $this->getCacheKeyIndex($version, $requestType);
        $this->cache->save(self::RESOURCES_KEY_PREFIX . $keyIndex, $allResources);
        $this->cache->save(self::ACCESSIBLE_RESOURCES_KEY_PREFIX . $keyIndex, $accessibleResources);
        $this->cache->save(self::EXCLUDED_ACTIONS_KEY_PREFIX . $keyIndex, $excludedActions);
    }

    /**
     * Puts Data API resources that do not have an identifier into the cache.
     *
     * @param string        $version             The Data API version
     * @param RequestType   $requestType         The request type, for example "rest", "soap", etc.
     * @param string[]      $resourcesWithoutId  The list of resources without identifier
     */
    public function saveResourcesWithoutIdentifier(
        string $version,
        RequestType $requestType,
        array $resourcesWithoutId
    ): void {
        $this->cache->save(
            self::RESOURCES_WITHOUT_ID_KEY_PREFIX . $this->getCacheKeyIndex($version, $requestType),
            $resourcesWithoutId
        );
    }

    /**
     * Puts sub-resources for all entities into the cache.
     *
     * @param string                    $version      The Data API version
     * @param RequestType               $requestType  The request type, for example "rest", "soap", etc.
     * @param ApiResourceSubresources[] $subresources The list of sub-resources
     */
    public function saveSubresources(string $version, RequestType $requestType, array $subresources): void
    {
        $keyIndex = self::SUBRESOURCE_KEY_PREFIX . $this->getCacheKeyIndex($version, $requestType);
        foreach ($subresources as $entitySubresources) {
            $this->cache->save(
                $keyIndex . $entitySubresources->getEntityClass(),
                $this->serializeApiResourceSubresources($entitySubresources)
            );
        }
    }

    /**
     * Deletes all Data API resources from the cache.
     */
    public function clear(): void
    {
        $this->cache->deleteAll();
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return string
     */
    private function getCacheKeyIndex(string $version, RequestType $requestType): string
    {
        return $version . (string)$requestType;
    }

    /**
     * @param string $entityClass
     * @param array  $cachedData
     *
     * @return ApiResource
     */
    private function unserializeApiResource(string $entityClass, array $cachedData): ApiResource
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
    private function serializeApiResource(ApiResource $resource): array
    {
        return [
            $resource->getExcludedActions()
        ];
    }

    /**
     * @param string $entityClass
     * @param array  $cachedData
     *
     * @return ApiResourceSubresources
     */
    private function unserializeApiResourceSubresources(
        string $entityClass,
        array $cachedData
    ): ApiResourceSubresources {
        $resource = new ApiResourceSubresources($entityClass);
        foreach ($cachedData[0] as $associationName => $serializedSubresource) {
            $subresource = $resource->addSubresource($associationName);
            $subresource->setTargetClassName($serializedSubresource[0]);
            $subresource->setAcceptableTargetClassNames($serializedSubresource[1]);
            $subresource->setIsCollection($serializedSubresource[2]);
            $subresource->setExcludedActions($serializedSubresource[3]);
        }

        return $resource;
    }

    /**
     * @param ApiResourceSubresources $entitySubresources
     *
     * @return array
     */
    private function serializeApiResourceSubresources(ApiResourceSubresources $entitySubresources): array
    {
        $serializedSubresources = [];
        $subresources = $entitySubresources->getSubresources();
        foreach ($subresources as $associationName => $subresource) {
            $serializedSubresources[$associationName] = [
                $subresource->getTargetClassName(),
                $subresource->getAcceptableTargetClassNames(),
                $subresource->isCollection(),
                $subresource->getExcludedActions()
            ];
        }

        return [
            $serializedSubresources
        ];
    }
}
