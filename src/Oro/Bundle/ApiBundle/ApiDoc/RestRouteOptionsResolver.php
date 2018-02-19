<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * Adds all REST API routes to REST API Sandbox based on the current ApiDoc view and Data API configuration.
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

    /** @var RestActionMapper */
    private $actionMapper;

    /** @var array [request type + version => [entity type => ApiResource, ...], ...] */
    private $resources = [];

    /** @var array [request type + version => [path => true, ...], ...] */
    private $overrides = [];

    /**
     * @param string               $routeGroup
     * @param RestActionMapper     $actionMapper
     * @param RestDocViewDetector  $docViewDetector
     * @param ResourcesProvider    $resourcesProvider
     * @param SubresourcesProvider $subresourcesProvider
     * @param ValueNormalizer      $valueNormalizer
     */
    public function __construct(
        string $routeGroup,
        RestActionMapper $actionMapper,
        RestDocViewDetector $docViewDetector,
        ResourcesProvider $resourcesProvider,
        SubresourcesProvider $subresourcesProvider,
        ValueNormalizer $valueNormalizer
    ) {
        $this->routeGroup = $routeGroup;
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
        $group = $route->getOption(self::GROUP_OPTION);
        if ($group === 'rest_api_deprecated') {
            $routes->remove($routes->getName($route));
            return;
        }
        if ($group !== $this->routeGroup
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
            }
        }
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     */
    private function resolveTemplateRoute(Route $route, RouteCollectionAccessor $routes)
    {
        $resources = $this->getResources();
        if (!empty($resources)) {
            $routeName = $routes->getName($route);
            $actions = $this->actionMapper->getActions($routeName);
            if (!empty($actions)) {
                $this->adjustRoutes($routeName, $route, $routes, $resources, $actions);
            }
        }
        $route->setRequirement(self::ENTITY_ATTRIBUTE, '\w+');
        $route->setOption('hidden', true);
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param string                  $overridePath
     */
    private function resolveOverrideRoute(Route $route, RouteCollectionAccessor $routes, $overridePath)
    {
        if (0 !== strpos($overridePath, '/')) {
            $overridePath = '/' . $overridePath;
        }
        $this->overrides[$this->getCacheKey()][$overridePath] = true;
        $entityType = $route->getDefault(self::ENTITY_ATTRIBUTE);
        $methods = $route->getMethods();
        if (!$entityType || !empty($methods)) {
            throw new \LogicException(sprintf(
                'The route "%s" with option "%s" must have "%s" default value and do not have "methods" property.',
                $routes->getName($route),
                self::OVERRIDE_PATH_OPTION,
                self::ENTITY_ATTRIBUTE
            ));
        }
        $resource = $this->getResource($entityType);
        if (null === $resource) {
            throw new \LogicException(sprintf(
                'The route "%s" has default value "%s" equals to "%s" that is unknown entity type.',
                $routes->getName($route),
                self::ENTITY_ATTRIBUTE,
                $entityType
            ));
        }
        $actions = $this->getOverrideRouteActions($routes, $overridePath, $entityType);
        if (empty($actions)) {
            throw new \LogicException(sprintf(
                'The route "%s" has option "%s" equals to "%s",'
                . ' but a list of allowed API actions for this path is empty.',
                $routes->getName($route),
                self::OVERRIDE_PATH_OPTION,
                $overridePath
            ));
        }

        $this->adjustRoutes($routes->getName($route), $route, $routes, [$entityType => $resource], $actions);
        $route->setOption('hidden', true);
    }

    /**
     * @param RouteCollectionAccessor $routes
     * @param string                  $overridePath
     * @param string                  $entityType
     *
     * @return string[]
     */
    private function getOverrideRouteActions(RouteCollectionAccessor $routes, $overridePath, $entityType)
    {
        $routeNames = [
            $this->actionMapper->getItemRouteName(),
            $this->actionMapper->getListRouteName(),
            $this->actionMapper->getSubresourceRouteName(),
            $this->actionMapper->getRelationshipRouteName()
        ];
        foreach ($routeNames as $routeName) {
            $path = str_replace(
                self::ENTITY_PLACEHOLDER,
                $entityType,
                $routes->get($routeName)->getPath()
            );
            if ($overridePath === $path) {
                return $this->actionMapper->getActions($routeName);
            }
        }

        return [];
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
        if (isset($this->resources[$cacheKey])) {
            return $this->resources[$cacheKey];
        }

        $result = [];
        $version = $this->docViewDetector->getVersion();
        $requestType = $this->docViewDetector->getRequestType();
        $resources = $this->resourcesProvider->getResources($version, $requestType);
        foreach ($resources as $resource) {
            $entityType = $this->valueNormalizer->normalizeValue(
                $resource->getEntityClass(),
                DataType::ENTITY_TYPE,
                $requestType
            );
            $result[$entityType] = $resource;
        }

        $this->resources[$cacheKey] = $result;

        return $result;
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
        foreach ($resources as $entityType => $resource) {
            $entityClass = $resource->getEntityClass();
            foreach ($actions as $action) {
                if ($this->hasAttribute($route, self::ASSOCIATION_PLACEHOLDER)) {
                    $cache = $this->addSubresources(
                        $action,
                        $entityType,
                        $entityClass,
                        $routeName,
                        $route,
                        $routes,
                        $cache
                    );
                } elseif (!in_array($action, $resource->getExcludedActions(), true)) {
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
            str_replace(self::ENTITY_PLACEHOLDER, $entityType, $route->getPath()),
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
            $strictRoute->setPath(str_replace(self::ENTITY_PLACEHOLDER, $entityType, $strictRoute->getPath()));
            $strictRoute->setMethods($methods);
            $strictRoute->setDefault(self::ACTION_ATTRIBUTE, $action);
            $strictRoute->setDefault(self::ENTITY_ATTRIBUTE, $entityType);
            $requirements = $strictRoute->getRequirements();
            unset($requirements[self::ENTITY_ATTRIBUTE]);
            $strictRoute->setRequirements($requirements);
            if (!isset($this->overrides[$this->getCacheKey()][$strictRoute->getPath()])) {
                $cache->add(
                    sprintf('%s_%d', $routes->generateRouteName($routeName), $cache->count()),
                    $strictRoute
                );
            }
        }

        return $cache;
    }

    /**
     * @param string                  $action
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

        $entityRoutePath = str_replace(self::ENTITY_PLACEHOLDER, $entityType, $route->getPath());
        foreach ($subresources as $associationName => $subresource) {
            if (in_array($action, $subresource->getExcludedActions(), true)) {
                continue;
            }

            $methods = $this->getMethods($route, $action);
            $existingRoute = $routes->getByPath(
                str_replace(self::ASSOCIATION_PLACEHOLDER, $associationName, $entityRoutePath),
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
                    str_replace(
                        self::ASSOCIATION_PLACEHOLDER,
                        $associationName,
                        str_replace(self::ENTITY_PLACEHOLDER, $entityType, $strictRoute->getPath())
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
                        sprintf('%s_%d', $routes->generateRouteName($routeName), $cache->count()),
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
        return false !== strpos($route->getPath(), $placeholder);
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
