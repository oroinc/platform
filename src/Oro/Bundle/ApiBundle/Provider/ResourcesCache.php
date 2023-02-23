<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Provides access to API resources and sub-resources related cache.
 */
class ResourcesCache
{
    private const RESOURCES_KEY_PREFIX = 'resources_';
    private const SUBRESOURCE_KEY_PREFIX = 'subresource_';
    private const ACCESSIBLE_RESOURCES_KEY_PREFIX = 'accessible_';
    private const RESOURCES_WITHOUT_ID_KEY_PREFIX = 'resources_wid_';
    private const EXCLUDED_ACTIONS_KEY_PREFIX = 'excluded_actions_';

    private ResourcesCacheAccessor $cache;

    public function __construct(ResourcesCacheAccessor $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Fetches a list of entity classes accessible through API from the cache.
     *
     * @param string      $version     The API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return array|null [entity class => accessible flag] or NULL if the list is not cached yet
     */
    public function getAccessibleResources(string $version, RequestType $requestType): ?array
    {
        $resources = $this->cache->fetch($version, $requestType, self::ACCESSIBLE_RESOURCES_KEY_PREFIX);

        if (false === $resources) {
            return null;
        }

        return $resources;
    }

    /**
     * Fetches an information about excluded actions from the cache.
     *
     * @param string      $version     The API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return array|null [entity class => [action, ...]] or NULL if the list is not cached yet
     */
    public function getExcludedActions(string $version, RequestType $requestType): ?array
    {
        $excludedActions = $this->cache->fetch($version, $requestType, self::EXCLUDED_ACTIONS_KEY_PREFIX);

        if (false === $excludedActions) {
            return null;
        }

        return $excludedActions;
    }

    /**
     * Fetches all API resources from the cache.
     *
     * @param string      $version     The API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return ApiResource[]|null The list of API resources or NULL if it is not cached yet
     */
    public function getResources(string $version, RequestType $requestType): ?array
    {
        $resources = $this->cache->fetch($version, $requestType, self::RESOURCES_KEY_PREFIX);

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
     * @param string      $version     The API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return ApiResourceSubresources|null The list of sub-resources or NULL if it is not cached yet
     */
    public function getSubresources(
        string $entityClass,
        string $version,
        RequestType $requestType
    ): ?ApiResourceSubresources {
        $cachedData = $this->cache->fetch($version, $requestType, self::SUBRESOURCE_KEY_PREFIX . $entityClass);

        if (false === $cachedData) {
            return null;
        }

        return $this->unserializeApiResourceSubresources($entityClass, $cachedData);
    }

    /**
     * Fetches a list of entity classes for API resources that do not have an identifier.
     *
     * @param string      $version     The API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return string[]|null The list of class names or NULL if it is not cached yet
     */
    public function getResourcesWithoutIdentifier(string $version, RequestType $requestType): ?array
    {
        $resources = $this->cache->fetch($version, $requestType, self::RESOURCES_WITHOUT_ID_KEY_PREFIX);

        if (false === $resources) {
            return null;
        }

        return $resources;
    }

    /**
     * Puts API resources into the cache.
     *
     * @param string        $version             The API version
     * @param RequestType   $requestType         The request type, for example "rest", "soap", etc.
     * @param ApiResource[] $resources           The list of API resources
     * @param array         $accessibleResources The resources accessible through API
     * @param array         $excludedActions     The actions excluded from API
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

        $this->cache->save($version, $requestType, self::RESOURCES_KEY_PREFIX, $allResources);
        $this->cache->save($version, $requestType, self::ACCESSIBLE_RESOURCES_KEY_PREFIX, $accessibleResources);
        $this->cache->save($version, $requestType, self::EXCLUDED_ACTIONS_KEY_PREFIX, $excludedActions);
    }

    /**
     * Puts API resources that do not have an identifier into the cache.
     *
     * @param string        $version             The API version
     * @param RequestType   $requestType         The request type, for example "rest", "soap", etc.
     * @param string[]      $resourcesWithoutId  The list of resources without identifier
     */
    public function saveResourcesWithoutIdentifier(
        string $version,
        RequestType $requestType,
        array $resourcesWithoutId
    ): void {
        $this->cache->save($version, $requestType, self::RESOURCES_WITHOUT_ID_KEY_PREFIX, $resourcesWithoutId);
    }

    /**
     * Puts sub-resources for all entities into the cache.
     *
     * @param string                    $version      The API version
     * @param RequestType               $requestType  The request type, for example "rest", "soap", etc.
     * @param ApiResourceSubresources[] $subresources The list of sub-resources
     */
    public function saveSubresources(string $version, RequestType $requestType, array $subresources): void
    {
        foreach ($subresources as $entitySubresources) {
            $this->cache->save(
                $version,
                $requestType,
                self::SUBRESOURCE_KEY_PREFIX . $entitySubresources->getEntityClass(),
                $this->serializeApiResourceSubresources($entitySubresources)
            );
        }
    }

    /**
     * Deletes all API resources from the cache.
     */
    public function clear(): void
    {
        $this->cache->clear();
    }

    private function unserializeApiResource(string $entityClass, array $cachedData): ApiResource
    {
        $resource = new ApiResource($entityClass);
        $resource->setExcludedActions($cachedData[0]);

        return $resource;
    }

    private function serializeApiResource(ApiResource $resource): array
    {
        return [
            $resource->getExcludedActions()
        ];
    }

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
