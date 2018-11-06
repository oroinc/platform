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
    protected $processor;

    /** @var ResourcesCache */
    protected $resourcesCache;

    /** @var array [request cache key => [ApiResource, ...], ...] */
    protected $resources = [];

    /** @var array [request cache key => [entity class => accessible flag, ...], ...] */
    protected $accessibleResources = [];

    /** @var array [request cache key => [entity class => [action name, ...], ...], ...] */
    protected $excludedActions = [];

    /**
     * @param ActionProcessorInterface $processor
     * @param ResourcesCache           $resourcesCache
     */
    public function __construct(ActionProcessorInterface $processor, ResourcesCache $resourcesCache)
    {
        $this->processor = $processor;
        $this->resourcesCache = $resourcesCache;
    }

    /**
     * Gets a configuration of all resources for a given Data API version.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return ApiResource[]
     */
    public function getResources($version, RequestType $requestType)
    {
        return $this->loadResources($version, $requestType);
    }

    /**
     * Gets a list of resources accessible through Data API.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return string[] The list of class names
     */
    public function getAccessibleResources($version, RequestType $requestType)
    {
        $result = [];
        $accessibleResources = $this->loadAccessibleResources($version, $requestType);
        foreach ($accessibleResources as $entityClass => $isAccessible) {
            if ($isAccessible) {
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
    public function isResourceAccessible($entityClass, $version, RequestType $requestType)
    {
        $accessibleResources = $this->loadAccessibleResources($version, $requestType);

        return
            array_key_exists($entityClass, $accessibleResources)
            && $accessibleResources[$entityClass];
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
    public function isResourceKnown($entityClass, $version, RequestType $requestType)
    {
        $accessibleResources = $this->loadAccessibleResources($version, $requestType);

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
    public function getResourceExcludeActions($entityClass, $version, RequestType $requestType)
    {
        $excludedActions = $this->loadExcludedActions($version, $requestType);

        return array_key_exists($entityClass, $excludedActions)
            ? $excludedActions[$entityClass]
            : [];
    }

    /**
     * Removes all entries from the cache.
     */
    public function clearCache()
    {
        $this->resources = [];
        $this->accessibleResources = [];
        $this->excludedActions = [];
        $this->resourcesCache->clear();
    }

    /**
     * @param string $cacheKey
     *
     * @return bool
     */
    private function hasResourcesInMemoryCache($cacheKey)
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
        $cacheKey,
        array $resources,
        array $accessibleResources,
        array $excludedActions
    ) {
        $this->resources[$cacheKey] = $resources;
        $this->accessibleResources[$cacheKey] = $accessibleResources;
        $this->excludedActions[$cacheKey] = $excludedActions;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return ApiResource[]
     */
    private function loadResources($version, RequestType $requestType)
    {
        $cacheKey = $this->getCacheKey($version, $requestType);
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

            $resources = array_values($context->getResult()->toArray());
            $this->resourcesCache->saveResources(
                $version,
                $requestType,
                $resources,
                $context->getAccessibleResources()
            );
            $accessibleResources = $this->resourcesCache->getAccessibleResources($version, $requestType);
            $excludedActions = $this->resourcesCache->getExcludedActions($version, $requestType);
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
     *
     * @return array [entity class => accessible flag]
     */
    protected function loadAccessibleResources($version, RequestType $requestType)
    {
        $cacheKey = $this->getCacheKey($version, $requestType);
        if ($this->hasResourcesInMemoryCache($cacheKey)) {
            $accessibleResourcesForRequest = $this->accessibleResources[$cacheKey];
        } else {
            $this->loadResources($version, $requestType);
            $accessibleResourcesForRequest = $this->accessibleResources[$cacheKey];
        }

        return $accessibleResourcesForRequest;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array [entity class => [action name, ...]]
     */
    protected function loadExcludedActions($version, RequestType $requestType)
    {
        $cacheKey = $this->getCacheKey($version, $requestType);
        if ($this->hasResourcesInMemoryCache($cacheKey)) {
            $excludedActionsForRequest = $this->excludedActions[$cacheKey];
        } else {
            $this->loadResources($version, $requestType);
            $excludedActionsForRequest = $this->excludedActions[$cacheKey];
        }

        return $excludedActionsForRequest;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return string
     */
    private function getCacheKey($version, RequestType $requestType)
    {
        return $version . (string)$requestType;
    }
}
