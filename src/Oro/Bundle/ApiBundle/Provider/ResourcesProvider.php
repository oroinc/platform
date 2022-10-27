<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Processor\CollectResources\AddExcludedActions;
use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides an information about all registered API resources.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ResourcesProvider implements ResetInterface
{
    private const NOT_ACCESSIBLE = 0;
    private const ACCESSIBLE = 1;
    private const ACCESSIBLE_AS_ASSOCIATION = 2;

    private ActionProcessorInterface $processor;
    private ResourcesCache $resourcesCache;
    private ResourcesWithoutIdentifierLoader $resourcesWithoutIdentifierLoader;
    private ResourceCheckerInterface $resourceChecker;
    /** @var array [request cache key => [ApiResource, ...], ...] */
    private array $resources = [];
    /** @var array [request cache key => [entity class => accessible flag, ...], ...] */
    private array $accessibleResources = [];
    /** @var array [request cache key => [entity class => enable flag, ...], ...] */
    private array $enabledResources = [];
    /** @var array [request cache key => [entity class => [action name, ...], ...], ...] */
    private array $excludedActions = [];
    /** @var array [request cache key => [entity class, ...], ...] */
    private array $resourcesWithoutIdentifier = [];

    public function __construct(
        ActionProcessorInterface $processor,
        ResourcesCache $resourcesCache,
        ResourcesWithoutIdentifierLoader $resourcesWithoutIdentifierLoader,
        ResourceCheckerInterface $resourceChecker
    ) {
        $this->processor = $processor;
        $this->resourcesCache = $resourcesCache;
        $this->resourcesWithoutIdentifierLoader = $resourcesWithoutIdentifierLoader;
        $this->resourceChecker = $resourceChecker;
    }

    /**
     * Gets a configuration of all resources for a given API version.
     *
     * @param string      $version     The API version
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
     * Gets a list of resources accessible through API.
     *
     * @param string      $version     The API version
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
        foreach ($accessibleResources as $entityClass => $accessibleFlag) {
            if (($accessibleFlag & self::ACCESSIBLE) || \in_array($entityClass, $resourcesWithoutIdentifier, true)) {
                $result[] = $entityClass;
            }
        }

        return $result;
    }

    /**
     * Checks whether a given entity is accessible through API.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The API version
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

        return
            (($accessibleResources[$entityClass] ?? self::NOT_ACCESSIBLE) & self::ACCESSIBLE)
            || $this->isResourceWithoutIdentifier($entityClass, $version, $requestType);
    }

    /**
     * Checks whether a given entity is accessible as an association in API.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return bool
     */
    public function isResourceAccessibleAsAssociation(
        string $entityClass,
        string $version,
        RequestType $requestType
    ): bool {
        $accessibleResources = $this->loadAccessibleResources(
            $version,
            $requestType,
            $this->getCacheKey($version, $requestType)
        );

        return (($accessibleResources[$entityClass] ?? self::NOT_ACCESSIBLE) & self::ACCESSIBLE_AS_ASSOCIATION);
    }

    /**
     * Checks whether a given entity is configured to be used in API.
     * A known resource can be accessible or not via API.
     * To check whether a resource is accessible via API use isResourceAccessible() method.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The API version
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

        return \array_key_exists($entityClass, $accessibleResources);
    }

    /**
     * Checks whether a given entity is enabled for API.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $action      The API action, {@see \Oro\Bundle\ApiBundle\Request\ApiAction}
     * @param string      $version     The API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return bool
     */
    public function isResourceEnabled(
        string $entityClass,
        string $action,
        string $version,
        RequestType $requestType
    ): bool {
        $cacheKey = $action . $version . $requestType;
        if (isset($this->enabledResources[$cacheKey][$entityClass])) {
            return $this->enabledResources[$cacheKey][$entityClass];
        }

        $enabled = $this->resourceChecker->isResourceEnabled($entityClass, $action, $version, $requestType);
        $this->enabledResources[$cacheKey][$entityClass] = $enabled;

        return $enabled;
    }

    /**
     * Gets a list of actions that cannot be used in API from for a given entity.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The API version
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

        return \array_key_exists($entityClass, $excludedActions)
            ? $excludedActions[$entityClass]
            : [];
    }

    /**
     * Checks if the given entity has API resources to read data,
     * but does not have API resources to create and update data.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return bool
     */
    public function isReadOnlyResource(string $entityClass, string $version, RequestType $requestType): bool
    {
        $excludeMap = array_fill_keys(
            $this->getResourceExcludeActions($entityClass, $version, $requestType),
            true
        );

        return
            isset($excludeMap[ApiAction::UPDATE], $excludeMap[ApiAction::CREATE])
            && !isset($excludeMap[ApiAction::GET], $excludeMap[ApiAction::GET_LIST]);
    }

    /**
     * Gets a list of resources that do not have an identifier field.
     *
     * @param string      $version     The API version
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
     * @param string      $version     The API version
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

        return \in_array($entityClass, $resourcesWithoutIdentifier, true);
    }

    /**
     * Removes all entries from the cache.
     */
    public function clearCache(): void
    {
        $this->reset();
        $this->resourcesCache->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        $this->resources = [];
        $this->accessibleResources = [];
        $this->enabledResources = [];
        $this->excludedActions = [];
        $this->resourcesWithoutIdentifier = [];
    }

    private function hasResourcesInMemoryCache(string $cacheKey): bool
    {
        return \array_key_exists($cacheKey, $this->resources);
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
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
            // load data
            /** @var CollectResourcesContext $context */
            $context = $this->processor->createContext();
            $context->setVersion($version);
            $context->getRequestType()->set($requestType);
            $this->processor->process($context);

            // prepare loaded data
            $resources = array_values($context->getResult()->toArray());
            $accessibleResources = array_fill_keys(
                $context->getAccessibleAsAssociationResources(),
                self::ACCESSIBLE_AS_ASSOCIATION
            );
            $accessibleEntityClasses = $context->getAccessibleResources();
            foreach ($accessibleEntityClasses as $entityClass) {
                if (isset($accessibleResources[$entityClass])) {
                    $accessibleResources[$entityClass] |= self::ACCESSIBLE;
                } else {
                    $accessibleResources[$entityClass] = self::ACCESSIBLE;
                }
            }
            $excludedActions = [];
            foreach ($resources as $resource) {
                $entityClass = $resource->getEntityClass();
                if (!isset($accessibleResources[$entityClass])) {
                    $accessibleResources[$entityClass] = self::NOT_ACCESSIBLE;
                }
                $resourceExcludedActions = $resource->getExcludedActions();
                if (!empty($resourceExcludedActions)) {
                    $excludedActions[$entityClass] = $resourceExcludedActions;
                }
            }

            // add data to memory cache here because they can be requested by isResourceWithoutIdentifier method
            $this->addResourcesToMemoryCache(
                $cacheKey,
                $resources,
                $accessibleResources,
                $excludedActions
            );

            // 1) exclude "create", "update" and "delete" actions for resources with identifier
            //    when "get" action is excluded
            // 2) exclude "delete_list" action for resources with identifier
            //    when both "get" and "get_list" actions are excluded
            // 3) exclude "update_list" action for resources without identifier
            // 4) exclude "update_list" action for resources with identifier
            //    when both "create" and "update" actions are excluded
            $actionsDependOnGetAction = [ApiAction::CREATE, ApiAction::UPDATE, ApiAction::DELETE];
            $actionsConfig = $context->get(AddExcludedActions::ACTIONS_CONFIG_KEY);
            foreach ($resources as $resource) {
                $entityClass = $resource->getEntityClass();
                if (\in_array(ApiAction::GET, $resource->getExcludedActions(), true)
                    && !$this->isResourceWithoutIdentifier($entityClass, $version, $requestType)
                ) {
                    foreach ($actionsDependOnGetAction as $actionName) {
                        if (!\in_array($actionName, $resource->getExcludedActions(), true)
                            && !$this->isActionEnabledInConfig($actionsConfig, $entityClass, $actionName)
                        ) {
                            $resource->addExcludedAction($actionName);
                            $excludedActions[$entityClass] = $resource->getExcludedActions();
                        }
                    }
                    if (\in_array(ApiAction::GET_LIST, $resource->getExcludedActions(), true)
                        && !\in_array(ApiAction::DELETE_LIST, $resource->getExcludedActions(), true)
                        && !$this->isActionEnabledInConfig($actionsConfig, $entityClass, ApiAction::DELETE_LIST)
                    ) {
                        $resource->addExcludedAction(ApiAction::DELETE_LIST);
                        $excludedActions[$entityClass] = $resource->getExcludedActions();
                    }
                }
                if (!\in_array(ApiAction::UPDATE_LIST, $resource->getExcludedActions(), true)
                    && $this->isExcludeUpdateList($resource, $actionsConfig, $entityClass, $version, $requestType)
                ) {
                    $resource->addExcludedAction(ApiAction::UPDATE_LIST);
                    $excludedActions[$entityClass] = $resource->getExcludedActions();
                }
            }

            // add data to memory cache
            $this->addResourcesToMemoryCache(
                $cacheKey,
                $resources,
                $accessibleResources,
                $excludedActions
            );

            // save data to the cache
            $this->resourcesCache->saveResources(
                $version,
                $requestType,
                $resources,
                $accessibleResources,
                $excludedActions
            );
        }

        return $resources;
    }

    private function isExcludeUpdateList(
        ApiResource $resource,
        array $actionsConfig,
        string $entityClass,
        string $version,
        RequestType $requestType
    ): bool {
        if ($this->isResourceWithoutIdentifier($entityClass, $version, $requestType)) {
            return !$this->isActionEnabledInConfig($actionsConfig, $entityClass, ApiAction::UPDATE_LIST);
        }

        return
            \in_array(ApiAction::CREATE, $resource->getExcludedActions(), true)
            && \in_array(ApiAction::UPDATE, $resource->getExcludedActions(), true);
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     * @param string      $cacheKey
     *
     * @return array [entity class => accessible flag, ...]
     *               where the accessible flag is:
     *               - self::NOT_ACCESSIBLE when a resource is known but not accessible through API
     *               - self::ACCESSIBLE when a resource is accessible through API
     *               - self::ACCESSIBLE_AS_ASSOCIATION when a resource is accessible as an association in API
     *               - self::ACCESSIBLE | self::ACCESSIBLE_AS_ASSOCIATION when a resource is accessible through API
     *                                                                    and accessible as an association in API
     */
    private function loadAccessibleResources(string $version, RequestType $requestType, string $cacheKey): array
    {
        if (!$this->hasResourcesInMemoryCache($cacheKey)) {
            $this->loadResources($version, $requestType, $cacheKey);
        }

        return $this->accessibleResources[$cacheKey];
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
        if (!$this->hasResourcesInMemoryCache($cacheKey)) {
            $this->loadResources($version, $requestType, $cacheKey);
        }

        return $this->excludedActions[$cacheKey];
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
        if (\array_key_exists($cacheKey, $this->resourcesWithoutIdentifier)) {
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

    private function getCacheKey(string $version, RequestType $requestType): string
    {
        return $version . $requestType;
    }

    /**
     * @param ActionsConfig[] $actionsConfig
     * @param string          $entityClass
     * @param string          $action
     *
     * @return bool
     */
    private function isActionEnabledInConfig(array $actionsConfig, string $entityClass, string $action): bool
    {
        if (!isset($actionsConfig[$entityClass])) {
            return false;
        }

        $actionConfig = $actionsConfig[$entityClass]->getAction($action);
        if (null === $actionConfig || !$actionConfig->hasExcluded()) {
            return false;
        }

        return !$actionConfig->isExcluded();
    }
}
