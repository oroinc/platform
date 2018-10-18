<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * Provides an information about all registered Data API resources.
 */
class ResourcesProvider
{
    /** @var ActionProcessorInterface */
    private $processor;

    /** @var ResourcesCache */
    private $resourcesCache;

    /** @var ResourcesWithoutIdentifierLoader */
    private $resourcesWithoutIdentifierLoader;

    /** @var array [request cache key => [ApiResource, ...], ...] */
    private $resources = [];

    /** @var array [request cache key => [entity class => accessible flag, ...], ...] */
    private $accessibleResources = [];

    /** @var array [request cache key => [entity class => [action name, ...], ...], ...] */
    private $excludedActions = [];

    /** @var array [request cache key => [entity class, ...], ...] */
    private $resourcesWithoutIdentifier = [];

    /**
     * @param ActionProcessorInterface         $processor
     * @param ResourcesCache                   $resourcesCache
     * @param ResourcesWithoutIdentifierLoader $resourcesWithoutIdentifierLoader
     */
    public function __construct(
        ActionProcessorInterface $processor,
        ResourcesCache $resourcesCache,
        ResourcesWithoutIdentifierLoader $resourcesWithoutIdentifierLoader
    ) {
        $this->processor = $processor;
        $this->resourcesCache = $resourcesCache;
        $this->resourcesWithoutIdentifierLoader = $resourcesWithoutIdentifierLoader;
    }

    /**
     * Gets a configuration of all resources for a given Data API version.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return ApiResource[]
     */
    public function getResources(string $version, RequestType $requestType): array
    {
        return $this->loadResources(
            $version,
            $requestType,
            $this->getCacheKey($version, $requestType)
        );
    }

    /**
     * Gets a list of resources accessible through Data API.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return string[] The list of class names
     */
    public function getAccessibleResources(string $version, RequestType $requestType): array
    {
        $result = [];
        $cacheKey = $this->getCacheKey($version, $requestType);
        $accessibleResources = $this->loadAccessibleResources($version, $requestType, $cacheKey);
        $resourcesWithoutIdentifier = $this->loadResourcesWithoutIdentifier($version, $requestType, $cacheKey);
        foreach ($accessibleResources as $entityClass => $isAccessible) {
            if ($isAccessible || in_array($entityClass, $resourcesWithoutIdentifier, true)) {
                $result[] = $entityClass;
            }
        }

        return $result;
    }

    /**
     * Checks whether a given entity is accessible through Data API.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return bool
     */
    public function isResourceAccessible(string $entityClass, string $version, RequestType $requestType): bool
    {
        $accessibleResources = $this->loadAccessibleResources(
            $version,
            $requestType,
            $this->getCacheKey($version, $requestType)
        );

        if (!array_key_exists($entityClass, $accessibleResources)) {
            return false;
        }

        return
            $accessibleResources[$entityClass]
            || $this->isResourceWithoutIdentifier($entityClass, $version, $requestType);
    }

    /**
     * Checks whether a given entity is configured to be used in Data API.
     * A known resource can be accessible or not via Data API.
     * To check whether a resource is accessible via Data API use isResourceAccessible() method.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return bool
     */
    public function isResourceKnown(string $entityClass, string $version, RequestType $requestType): bool
    {
        $accessibleResources = $this->loadAccessibleResources(
            $version,
            $requestType,
            $this->getCacheKey($version, $requestType)
        );

        return array_key_exists($entityClass, $accessibleResources);
    }

    /**
     * Gets a list of actions that cannot be used in Data API from for a given entity.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return string[]
     */
    public function getResourceExcludeActions(string $entityClass, string $version, RequestType $requestType): array
    {
        $excludedActions = $this->loadExcludedActions(
            $version,
            $requestType,
            $this->getCacheKey($version, $requestType)
        );

        return array_key_exists($entityClass, $excludedActions)
            ? $excludedActions[$entityClass]
            : [];
    }

    /**
     * Gets a list of resources that do not have an identifier field.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return string[] The list of class names
     */
    public function getResourcesWithoutIdentifier(string $version, RequestType $requestType): array
    {
        return $this->loadResourcesWithoutIdentifier(
            $version,
            $requestType,
            $this->getCacheKey($version, $requestType)
        );
    }

    /**
     * Checks whether a given entity does not have an identifier field.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return bool
     */
    public function isResourceWithoutIdentifier(string $entityClass, string $version, RequestType $requestType): bool
    {
        $resourcesWithoutIdentifier = $this->loadResourcesWithoutIdentifier(
            $version,
            $requestType,
            $this->getCacheKey($version, $requestType)
        );

        return in_array($entityClass, $resourcesWithoutIdentifier, true);
    }

    /**
     * Removes all entries from the cache.
     */
    public function clearCache(): void
    {
        $this->resources = [];
        $this->accessibleResources = [];
        $this->excludedActions = [];
        $this->resourcesWithoutIdentifier = [];
        $this->resourcesCache->clear();
    }

    /**
     * @param string $cacheKey
     *
     * @return bool
     */
    private function hasResourcesInMemoryCache(string $cacheKey): bool
    {
        return array_key_exists($cacheKey, $this->resources);
    }

    /**
     * @param string        $cacheKey
     * @param ApiResource[] $resources
     * @param array         $accessibleResources
     * @param array         $excludedActions
     */
    private function addResourcesToMemoryCache(
        string $cacheKey,
        array $resources,
        array $accessibleResources,
        array $excludedActions
    ): void {
        $this->resources[$cacheKey] = $resources;
        $this->accessibleResources[$cacheKey] = $accessibleResources;
        $this->excludedActions[$cacheKey] = $excludedActions;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     * @param string      $cacheKey
     *
     * @return ApiResource[]
     */
    private function loadResources(string $version, RequestType $requestType, string $cacheKey): array
    {
        if ($this->hasResourcesInMemoryCache($cacheKey)) {
            return $this->resources[$cacheKey];
        }

        $isResourcesMemoryCacheInitialized = false;
        $resources = $this->resourcesCache->getResources($version, $requestType);
        if (null !== $resources) {
            $accessibleResources = $this->resourcesCache->getAccessibleResources($version, $requestType);
            if (null !== $accessibleResources) {
                $excludedActions = $this->resourcesCache->getExcludedActions($version, $requestType);
                if (null !== $excludedActions) {
                    $this->addResourcesToMemoryCache(
                        $cacheKey,
                        $resources,
                        $accessibleResources,
                        $excludedActions
                    );
                    $isResourcesMemoryCacheInitialized = true;
                }
            }
        }
        if (!$isResourcesMemoryCacheInitialized) {
            /** @var CollectResourcesContext $context */
            $context = $this->processor->createContext();
            $context->setVersion($version);
            $context->getRequestType()->set($requestType);
            $this->processor->process($context);

            /** @var ApiResource[] $resources */
            $resources = array_values($context->getResult()->toArray());
            $accessibleResources = array_fill_keys($context->getAccessibleResources(), true);
            $excludedActions = [];
            foreach ($resources as $resource) {
                $entityClass = $resource->getEntityClass();
                if (!isset($accessibleResources[$entityClass])) {
                    $accessibleResources[$entityClass] = false;
                }
                $resourceExcludedActions = $resource->getExcludedActions();
                if (!empty($resourceExcludedActions)) {
                    $excludedActions[$entityClass] = $resourceExcludedActions;
                }
            }

            $this->resourcesCache->saveResources(
                $version,
                $requestType,
                $resources,
                $accessibleResources,
                $excludedActions
            );
            $this->addResourcesToMemoryCache(
                $cacheKey,
                $resources,
                $accessibleResources,
                $excludedActions
            );
        }

        return $resources;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     * @param string      $cacheKey
     *
     * @return array [entity class => accessible flag]
     */
    private function loadAccessibleResources(string $version, RequestType $requestType, string $cacheKey): array
    {
        if ($this->hasResourcesInMemoryCache($cacheKey)) {
            $accessibleResourcesForRequest = $this->accessibleResources[$cacheKey];
        } else {
            $this->loadResources($version, $requestType, $cacheKey);
            $accessibleResourcesForRequest = $this->accessibleResources[$cacheKey];
        }

        return $accessibleResourcesForRequest;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     * @param string      $cacheKey
     *
     * @return array [entity class => [action name, ...]]
     */
    private function loadExcludedActions(string $version, RequestType $requestType, string $cacheKey): array
    {
        if ($this->hasResourcesInMemoryCache($cacheKey)) {
            $excludedActionsForRequest = $this->excludedActions[$cacheKey];
        } else {
            $this->loadResources($version, $requestType, $cacheKey);
            $excludedActionsForRequest = $this->excludedActions[$cacheKey];
        }

        return $excludedActionsForRequest;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     * @param string      $cacheKey
     *
     * @return string[]
     */
    private function loadResourcesWithoutIdentifier(string $version, RequestType $requestType, string $cacheKey): array
    {
        if (array_key_exists($cacheKey, $this->resourcesWithoutIdentifier)) {
            $resourcesWithoutId = $this->resourcesWithoutIdentifier[$cacheKey];
        } else {
            $resourcesWithoutId = $this->resourcesCache->getResourcesWithoutIdentifier($version, $requestType);
            if (null === $resourcesWithoutId) {
                $resourcesWithoutId = $this->resourcesWithoutIdentifierLoader->load(
                    $version,
                    $requestType,
                    $this->loadResources($version, $requestType, $cacheKey)
                );
                $this->resourcesCache->saveResourcesWithoutIdentifier($version, $requestType, $resourcesWithoutId);
            }

            $this->resourcesWithoutIdentifier[$cacheKey] = $resourcesWithoutId;
        }

        return $resourcesWithoutId;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return string
     */
    private function getCacheKey(string $version, RequestType $requestType): string
    {
        return $version . (string)$requestType;
    }
}
