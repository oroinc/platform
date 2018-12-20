<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds all REST API routes to API sandbox based on the current API view and Data API configuration.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RestRouteOptionsResolver implements RouteOptionsResolverInterface
{
    public const ENTITY_ATTRIBUTE        = 'entity';
    public const ENTITY_PLACEHOLDER      = '{entity}';
    public const ASSOCIATION_ATTRIBUTE   = 'association';
    public const ASSOCIATION_PLACEHOLDER = '{association}';
    public const ACTION_ATTRIBUTE        = '_action';
    public const GROUP_OPTION            = 'group';
    public const OVERRIDE_PATH_OPTION    = 'override_path';

    private const HIDDEN_OPTION = 'hidden';

    /** @var string The group of routes that should be processed by this resolver */
    private $routeGroup;

    /** @var RestDocViewDetector */
    private $docViewDetector;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    /** @var SubresourcesProvider */
    private $subresourcesProvider;

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var RestRoutes */
    private $routes;

    /** @var RestActionMapper */
    private $actionMapper;

    /** @var array [request type + version => [entity type => ApiResource, ...], ...] */
    private $resources = [];

    /** @var array [request type + version => [entity type => ApiResource, ...], ...] */
    private $resourcesWithoutIdentifier = [];

    /** @var array [request type + version => [path => true, ...], ...] */
    private $overrides = [];

    /**
     * @param string               $routeGroup
     * @param RestRoutes           $routes
     * @param RestActionMapper     $actionMapper
     * @param RestDocViewDetector  $docViewDetector
     * @param ResourcesProvider    $resourcesProvider
     * @param SubresourcesProvider $subresourcesProvider
     * @param ValueNormalizer      $valueNormalizer
     */
    public function __construct(
        string $routeGroup,
        RestRoutes $routes,
        RestActionMapper $actionMapper,
        RestDocViewDetector $docViewDetector,
        ResourcesProvider $resourcesProvider,
        SubresourcesProvider $subresourcesProvider,
        ValueNormalizer $valueNormalizer
    ) {
        $this->routeGroup = $routeGroup;
        $this->routes = $routes;
        $this->actionMapper = $actionMapper;
        $this->docViewDetector = $docViewDetector;
        $this->resourcesProvider = $resourcesProvider;
        $this->subresourcesProvider = $subresourcesProvider;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if ($route->getOption(self::GROUP_OPTION) !== $this->routeGroup
            || $this->docViewDetector->getRequestType()->isEmpty()
        ) {
            return;
        }

        if ($this->hasAttribute($route, self::ENTITY_PLACEHOLDER)) {
            $this->resolveTemplateRoute($route, $routes);
        } else {
            $overridePath = $route->getOption(self::OVERRIDE_PATH_OPTION);
            if ($overridePath) {
                $this->resolveOverrideRoute($route, $routes, $overridePath);
            } else {
                $entityType = $route->getDefault(self::ENTITY_ATTRIBUTE);
                if ($entityType && $this->isResourceWithoutIdentifier($entityType)) {
                    $route->setOption(self::HIDDEN_OPTION, true);
                }
            }
        }
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     */
    private function resolveTemplateRoute(Route $route, RouteCollectionAccessor $routes)
    {
        $routeName = $routes->getName($route);
        $resources = $this->getResources();
        if (!empty($resources)) {
            $actions = $this->actionMapper->getActions($routeName);
            if (!empty($actions)) {
                $this->adjustRoutes($routeName, $route, $routes, $resources, $actions);
            }
        }
        if ($this->routes->getListRouteName() === $routeName) {
            $resources = $this->getResourcesWithoutIdentifier();
            if (!empty($resources)) {
                $actions = $this->actionMapper->getActionsForResourcesWithoutIdentifier();
                $this->adjustRoutes($routeName, $route, $routes, $resources, $actions);
            }
        }
        $route->setRequirement(self::ENTITY_ATTRIBUTE, '\w+');
        $route->setOption(self::HIDDEN_OPTION, true);
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param string                  $overridePath
     */
    private function resolveOverrideRoute(Route $route, RouteCollectionAccessor $routes, $overridePath)
    {
        if (0 !== \strpos($overridePath, '/')) {
            $overridePath = '/' . $overridePath;
        }
        $this->overrides[$this->getCacheKey()][$overridePath] = true;

        $methods = $route->getMethods();
        if (!empty($methods)) {
            throw new \LogicException(\sprintf(
                'The route "%s" with option "%s" must do not have "methods" property.',
                $routes->getName($route),
                self::OVERRIDE_PATH_OPTION
            ));
        }
        $entityType = $route->getDefault(self::ENTITY_ATTRIBUTE);
        if (!$entityType) {
            throw new \LogicException(\sprintf(
                'The route "%s" with option "%s" must have "%s" default value.',
                $routes->getName($route),
                self::OVERRIDE_PATH_OPTION,
                self::ENTITY_ATTRIBUTE
            ));
        }
        $resource = $this->getResource($entityType);
        if (null === $resource) {
            throw new \LogicException(\sprintf(
                'The route "%s" has default value "%s" equals to "%s" that is unknown entity type.',
                $routes->getName($route),
                self::ENTITY_ATTRIBUTE,
                $entityType
            ));
        }
        $overrideRouteName = $this->getOverrideRouteName($routes, $overridePath, $entityType);
        if (!$overrideRouteName) {
            throw new \LogicException(\sprintf(
                'The route "%s" has option "%s" equals to "%s",'
                . ' but it is not possible to determine the route name for it.',
                $routes->getName($route),
                self::OVERRIDE_PATH_OPTION,
                $overridePath
            ));
        }
        $actions = $this->actionMapper->getActions($overrideRouteName);
        if (empty($actions)) {
            throw new \LogicException(\sprintf(
                'The route "%s" has option "%s" equals to "%s",'
                . ' it is matched to "%s" route, but a list of allowed API actions for this route is empty.',
                $routes->getName($route),
                self::OVERRIDE_PATH_OPTION,
                $overridePath,
                $overrideRouteName
            ));
        }

        $this->adjustRoutes($routes->getName($route), $route, $routes, [$entityType => $resource], $actions);
        $route->setOption(self::HIDDEN_OPTION, true);
    }

    /**
     * @param RouteCollectionAccessor $routes
     * @param string                  $overridePath
     * @param string                  $entityType
     *
     * @return string|null
     */
    private function getOverrideRouteName(RouteCollectionAccessor $routes, $overridePath, $entityType)
    {
        $result = null;
        $routeNames = [
            $this->routes->getItemRouteName(),
            $this->routes->getListRouteName(),
            $this->routes->getSubresourceRouteName(),
            $this->routes->getRelationshipRouteName()
        ];
        foreach ($routeNames as $routeName) {
            $routePath = \str_replace(
                self::ENTITY_PLACEHOLDER,
                $entityType,
                $routes->get($routeName)->getPath()
            );
            if ($overridePath === $routePath) {
                $result = $routeName;
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $entityType
     *
     * @return ApiResource|null
     */
    private function getResource($entityType)
    {
        $result = null;
        $resources = $this->getResources();
        if (isset($resources[$entityType])) {
            $result = $resources[$entityType];
        }

        return $result;
    }

    /**
     * @return ApiResource[] [entity type => ApiResource, ...]
     */
    private function getResources()
    {
        $cacheKey = $this->getCacheKey();
        $this->ensureResourcesLoaded($cacheKey);

        return $this->resources[$cacheKey];
    }

    /**
     * @return ApiResource[] [entity type => ApiResource, ...]
     */
    private function getResourcesWithoutIdentifier()
    {
        $cacheKey = $this->getCacheKey();
        $this->ensureResourcesLoaded($cacheKey);

        return $this->resourcesWithoutIdentifier[$cacheKey];
    }

    /**
     * @param string $entityType
     *
     * @return bool
     */
    private function isResourceWithoutIdentifier($entityType)
    {
        $resources = $this->getResourcesWithoutIdentifier();

        return isset($resources[$entityType]);
    }

    /**
     * @param string $cacheKey
     */
    private function ensureResourcesLoaded($cacheKey)
    {
        if (isset($this->resources[$cacheKey])) {
            return;
        }

        $resources = [];
        $resourcesWithoutIdentifier = [];

        $version = $this->docViewDetector->getVersion();
        $requestType = $this->docViewDetector->getRequestType();
        $allResources = $this->resourcesProvider->getResources($version, $requestType);
        foreach ($allResources as $resource) {
            $entityClass = $resource->getEntityClass();
            $entityType = $this->valueNormalizer->normalizeValue($entityClass, DataType::ENTITY_TYPE, $requestType);
            if ($this->resourcesProvider->isResourceWithoutIdentifier($entityClass, $version, $requestType)) {
                $resourcesWithoutIdentifier[$entityType] = $resource;
            } else {
                $resources[$entityType] = $resource;
            }
        }

        $this->resources[$cacheKey] = $resources;
        $this->resourcesWithoutIdentifier[$cacheKey] = $resourcesWithoutIdentifier;
    }

    /**
     * @param $entityClass
     *
     * @return ApiSubresource[]
     */
    private function getSubresources($entityClass)
    {
        $entitySubresources = $this->subresourcesProvider->getSubresources(
            $entityClass,
            $this->docViewDetector->getVersion(),
            $this->docViewDetector->getRequestType()
        );

        return $entitySubresources->getSubresources();
    }

    /**
     * @param string                  $routeName
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param ApiResource[]           $resources [entity type => ApiResource, ...]
     * @param string[]                $actions
     */
    private function adjustRoutes(
        $routeName,
        Route $route,
        RouteCollectionAccessor $routes,
        array $resources,
        array $actions
    ) {
        $cache = new RouteCollection();
        $isSubresource = $this->hasAttribute($route, self::ASSOCIATION_PLACEHOLDER);
        foreach ($resources as $entityType => $resource) {
            $entityClass = $resource->getEntityClass();
            foreach ($actions as $action) {
                if ($isSubresource) {
                    $cache = $this->addSubresources(
                        $action,
                        $actions,
                        $entityType,
                        $entityClass,
                        $routeName,
                        $route,
                        $routes,
                        $cache
                    );
                } elseif (!$this->isExcludedAction($action, $actions, $resource->getExcludedActions())) {
                    $cache = $this->addResource(
                        $action,
                        $entityType,
                        $routeName,
                        $route,
                        $routes,
                        $cache
                    );
                }
            }
        }
        // add cached routes before the current route
        if ($cache->count()) {
            $routes->insertCollection($cache, $routeName, true);
        }
    }

    /**
     * @param string   $action
     * @param string[] $otherActions
     * @param string[] $excludedActions
     *
     * @return bool
     */
    private function isExcludedAction($action, $otherActions, $excludedActions)
    {
        if (ApiActions::OPTIONS === $action) {
            return !$this->hasOtherActions($action, $otherActions, $excludedActions);
        }

        return \in_array($action, $excludedActions, true);
    }

    /**
     * @param string   $action
     * @param string[] $otherActions
     * @param string[] $excludedActions
     *
     * @return bool
     */
    private function hasOtherActions($action, $otherActions, $excludedActions)
    {
        foreach ($otherActions as $otherAction) {
            if ($otherAction !== $action && !\in_array($otherAction, $excludedActions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Route  $route
     * @param string $action
     *
     * @return string[]
     */
    private function getMethods(Route $route, $action)
    {
        $methods = $route->getMethods();
        if (empty($methods)) {
            $methods = [$this->actionMapper->getMethod($action)];
        }

        return $methods;
    }

    /**
     * @param string                  $action
     * @param string                  $entityType
     * @param string                  $routeName
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param RouteCollection         $cache
     *
     * @return RouteCollection
     */
    private function addResource(
        $action,
        $entityType,
        $routeName,
        Route $route,
        RouteCollectionAccessor $routes,
        RouteCollection $cache
    ) {
        $methods = $this->getMethods($route, $action);
        $existingRoute = $routes->getByPath(
            \str_replace(self::ENTITY_PLACEHOLDER, $entityType, $route->getPath()),
            $methods
        );
        if ($existingRoute) {
            // add cached routes before the current route and reset the cache
            if ($cache->count()) {
                $routes->insertCollection($cache, $routeName, true);
                $cache = new RouteCollection();
            }
            // move existing route before the current route
            $routes->insert($routes->getName($existingRoute), $existingRoute, $routeName, true);
        } else {
            // add an additional strict route based on the base route and current entity
            $strictRoute = $routes->cloneRoute($route);
            $strictRoute->setPath(\str_replace(self::ENTITY_PLACEHOLDER, $entityType, $strictRoute->getPath()));
            $strictRoute->setMethods($methods);
            $strictRoute->setDefault(self::ACTION_ATTRIBUTE, $action);
            $strictRoute->setDefault(self::ENTITY_ATTRIBUTE, $entityType);
            $requirements = $strictRoute->getRequirements();
            unset($requirements[self::ENTITY_ATTRIBUTE]);
            $strictRoute->setRequirements($requirements);
            if (!isset($this->overrides[$this->getCacheKey()][$strictRoute->getPath()])) {
                $cache->add(
                    \sprintf('%s_%d', $routes->generateRouteName($routeName), $cache->count()),
                    $strictRoute
                );
            }
        }

        return $cache;
    }

    /**
     * @param string                  $action
     * @param string[]                $otherActions
     * @param string                  $entityType
     * @param string                  $entityClass
     * @param string                  $routeName
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param RouteCollection         $cache
     *
     * @return RouteCollection
     */
    private function addSubresources(
        $action,
        $otherActions,
        $entityType,
        $entityClass,
        $routeName,
        Route $route,
        RouteCollectionAccessor $routes,
        RouteCollection $cache
    ) {
        $subresources = $this->getSubresources($entityClass);
        if (empty($subresources)) {
            return $cache;
        }

        $entityRoutePath = \str_replace(self::ENTITY_PLACEHOLDER, $entityType, $route->getPath());
        foreach ($subresources as $associationName => $subresource) {
            if ($this->isExcludedAction($action, $otherActions, $subresource->getExcludedActions())) {
                continue;
            }

            $methods = $this->getMethods($route, $action);
            $existingRoute = $routes->getByPath(
                \str_replace(self::ASSOCIATION_PLACEHOLDER, $associationName, $entityRoutePath),
                $methods
            );
            if ($existingRoute) {
                // add cached routes before the current route and reset the cache
                if ($cache->count()) {
                    $routes->insertCollection($cache, $routeName, true);
                    $cache = new RouteCollection();
                }
                // move existing route before the current route
                $routes->insert($routes->getName($existingRoute), $existingRoute, $routeName, true);
            } else {
                // add an additional strict route based on the base route and current entity
                $strictRoute = $routes->cloneRoute($route);
                $strictRoute->setPath(
                    \str_replace(
                        self::ASSOCIATION_PLACEHOLDER,
                        $associationName,
                        \str_replace(self::ENTITY_PLACEHOLDER, $entityType, $strictRoute->getPath())
                    )
                );
                $strictRoute->setMethods($methods);
                $strictRoute->setDefault(self::ACTION_ATTRIBUTE, $action);
                $strictRoute->setDefault(self::ENTITY_ATTRIBUTE, $entityType);
                $strictRoute->setDefault(self::ASSOCIATION_ATTRIBUTE, $associationName);
                $requirements = $strictRoute->getRequirements();
                unset($requirements[self::ENTITY_ATTRIBUTE], $requirements[self::ASSOCIATION_ATTRIBUTE]);
                $strictRoute->setRequirements($requirements);
                if (!isset($this->overrides[$this->getCacheKey()][$strictRoute->getPath()])) {
                    $cache->add(
                        \sprintf('%s_%d', $routes->generateRouteName($routeName), $cache->count()),
                        $strictRoute
                    );
                }
            }
        }

        return $cache;
    }

    /**
     * Checks if a route has the given placeholder in a path.
     *
     * @param Route  $route
     * @param string $placeholder
     *
     * @return bool
     */
    private function hasAttribute(Route $route, $placeholder)
    {
        return false !== \strpos($route->getPath(), $placeholder);
    }

    /**
     * @return string
     */
    private function getCacheKey()
    {
        $version = $this->docViewDetector->getVersion();
        $requestType = $this->docViewDetector->getRequestType();

        return $version . (string)$requestType;
    }
}
