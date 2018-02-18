<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Provides a list of all registered Data API resources
 * and information which of entities are accesible via Data API.
 */
class ResourcesProvider
{
    /** @var ActionProcessorInterface */
    private $processor;

    /** @var ResourcesCache */
    private $resourcesCache;

    /** @var array [request cache key => [entity class => accessible flag]] */
    private $accessibleResources = [];

    /** @var array [request cache key => [entity class => [action name, ...]]] */
    private $excludedActions = [];

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
    public function getResources($version, RequestType $requestType): array
    {
        $resources = $this->resourcesCache->getResources($version, $requestType);
        if (null !== $resources) {
            return $resources;
        }

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

        return $resources;
    }

    /**
     * Gets a list of resources accessible through Data API.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return string[] The list of class names
     */
    public function getAccessibleResources($version, RequestType $requestType): array
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
    public function isResourceAccessible($entityClass, $version, RequestType $requestType): bool
    {
        $accessibleResources = $this->loadAccessibleResources($version, $requestType);

        return
            array_key_exists($entityClass, $accessibleResources)
            && $accessibleResources[$entityClass];
    }

    /**
     * Checks whether a given entity is configured to be used in Data API.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return bool
     */
    public function isResourceKnown($entityClass, $version, RequestType $requestType): bool
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
    public function getResourceExcludeActions($entityClass, $version, RequestType $requestType): array
    {
        $excludedActions = $this->loadExcludedActions($version, $requestType);

        return array_key_exists($entityClass, $excludedActions)
            ? $excludedActions[$entityClass]
            : [];
    }

    /**
     * Removes all entries from the cache.
     */
    public function clearCache(): void
    {
        $this->accessibleResources = [];
        $this->excludedActions = [];
        $this->resourcesCache->clear();
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array [entity class => accessible flag]
     */
    private function loadAccessibleResources($version, RequestType $requestType): array
    {
        $cacheIndex = $this->getCacheKeyIndex($version, $requestType);
        if (!array_key_exists($cacheIndex, $this->accessibleResources)) {
            $accessibleResourcesForRequest = $this->resourcesCache->getAccessibleResources($version, $requestType);
            if (null === $accessibleResourcesForRequest) {
                $this->getResources($version, $requestType);
                $accessibleResourcesForRequest = $this->resourcesCache->getAccessibleResources($version, $requestType);
            }

            $this->accessibleResources[$cacheIndex] = $accessibleResourcesForRequest;
        } else {
            $accessibleResourcesForRequest = $this->accessibleResources[$cacheIndex];
        }

        return $accessibleResourcesForRequest;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array [entity class => [action name, ...]]
     */
    private function loadExcludedActions($version, RequestType $requestType): array
    {
        $cacheIndex = $this->getCacheKeyIndex($version, $requestType);
        if (!array_key_exists($cacheIndex, $this->excludedActions)) {
            $excludedActionsForRequest = $this->resourcesCache->getExcludedActions($version, $requestType);
            if (null === $excludedActionsForRequest) {
                $this->getResources($version, $requestType);
                $excludedActionsForRequest = $this->resourcesCache->getExcludedActions($version, $requestType);
            }

            $this->excludedActions[$cacheIndex] = $excludedActionsForRequest;
        } else {
            $excludedActionsForRequest = $this->excludedActions[$cacheIndex];
        }

        return $excludedActionsForRequest;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return string
     */
    private function getCacheKeyIndex($version, RequestType $requestType): string
    {
        return $version . (string)$requestType;
    }
}
