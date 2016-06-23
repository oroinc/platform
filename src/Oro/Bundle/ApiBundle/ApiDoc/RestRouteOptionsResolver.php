<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

class RestRouteOptionsResolver implements RouteOptionsResolverInterface
{
    const ROUTE_GROUP = 'rest_api';

    const ENTITY_ATTRIBUTE        = 'entity';
    const ENTITY_PLACEHOLDER      = '{entity}';
    const ASSOCIATION_ATTRIBUTE   = 'association';
    const ASSOCIATION_PLACEHOLDER = '{association}';

    /** @var RestDocViewDetector */
    protected $docViewDetector;

    /** @var ResourcesProvider */
    protected $resourcesProvider;

    /** @var SubresourcesProvider */
    protected $subresourcesProvider;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var array [entity type => ApiResource, ...] */
    protected $resources;

    /**
     * @param RestDocViewDetector  $docViewDetector
     * @param ResourcesProvider    $resourcesProvider
     * @param SubresourcesProvider $subresourcesProvider
     * @param ValueNormalizer      $valueNormalizer
     */
    public function __construct(
        RestDocViewDetector $docViewDetector,
        ResourcesProvider $resourcesProvider,
        SubresourcesProvider $subresourcesProvider,
        ValueNormalizer $valueNormalizer
    ) {
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
        $group = $route->getOption('group');
        if ($group === 'rest_api_deprecated') {
            $routes->remove($routes->getName($route));
            return;
        }
        if ($group !== self::ROUTE_GROUP
            || $this->docViewDetector->getRequestType()->isEmpty()
        ) {
            return;
        }

        if ($this->hasAttribute($route, self::ENTITY_PLACEHOLDER)) {
            $resources = $this->getResources();
            if (!empty($resources)) {
                $this->adjustRoutes($route, $routes, $resources);
            }
            $route->setRequirement(self::ENTITY_ATTRIBUTE, '\w+');

            $route->setOption('hidden', true);
        }
    }

    /**
     * @return ApiResource[] [entity type => ApiResource, ...]
     */
    protected function getResources()
    {
        if (null === $this->resources) {
            $this->resources = [];
            $resources = $this->resourcesProvider->getResources(
                $this->docViewDetector->getVersion(),
                $this->docViewDetector->getRequestType()
            );
            foreach ($resources as $resource) {
                $entityType = $this->valueNormalizer->normalizeValue(
                    $resource->getEntityClass(),
                    DataType::ENTITY_TYPE,
                    $this->docViewDetector->getRequestType()
                );
                $this->resources[$entityType] = $resource;
            }
        }

        return $this->resources;
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param ApiResource[]           $resources [entity type => ApiResource, ...]
     */
    protected function adjustRoutes(Route $route, RouteCollectionAccessor $routes, $resources)
    {
        $routeName = $routes->getName($route);

        $action = $route->getDefault('_action');
        foreach ($resources as $entityType => $resource) {
            $entityClass = $resource->getEntityClass();
            if ($this->hasAttribute($route, self::ASSOCIATION_PLACEHOLDER)) {
                $this->addSubresources($action, $entityType, $entityClass, $routeName, $route, $routes);
            } elseif (!in_array($action, $resource->getExcludedActions(), true)) {
                $this->addResource($entityType, $routeName, $route, $routes);
            }
        }
    }

    /**
     * @param string                  $entityType
     * @param string                  $routeName
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     */
    protected function addResource(
        $entityType,
        $routeName,
        Route $route,
        RouteCollectionAccessor $routes
    ) {
        $existingRoute = $routes->getByPath(
            str_replace(self::ENTITY_PLACEHOLDER, $entityType, $route->getPath()),
            $route->getMethods()
        );
        if ($existingRoute) {
            // move existing route before the current route
            $existingRouteName = $routes->getName($existingRoute);
            $routes->remove($existingRouteName);
            $routes->insert($existingRouteName, $existingRoute, $routeName, true);
        } else {
            // add an additional strict route based on the base route and current entity
            $strictRoute = $routes->cloneRoute($route);
            $strictRoute->setPath(str_replace(self::ENTITY_PLACEHOLDER, $entityType, $strictRoute->getPath()));
            $strictRoute->setDefault(self::ENTITY_ATTRIBUTE, $entityType);
            $requirements = $strictRoute->getRequirements();
            unset($requirements[self::ENTITY_ATTRIBUTE]);
            $strictRoute->setRequirements($requirements);
            $routes->insert(
                $routes->generateRouteName($routeName),
                $strictRoute,
                $routeName,
                true
            );
        }
    }

    /**
     * @param string                  $action
     * @param string                  $entityType
     * @param string                  $entityClass
     * @param string                  $routeName
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     */
    protected function addSubresources(
        $action,
        $entityType,
        $entityClass,
        $routeName,
        Route $route,
        RouteCollectionAccessor $routes
    ) {
        $entitySubresources = $this->subresourcesProvider->getSubresources(
            $entityClass,
            $this->docViewDetector->getVersion(),
            $this->docViewDetector->getRequestType()
        );
        $subresources = $entitySubresources->getSubresources();
        if (empty($subresources)) {
            return;
        }

        $entityRoutePath = str_replace(self::ENTITY_PLACEHOLDER, $entityType, $route->getPath());
        foreach ($subresources as $associationName => $subresource) {
            if (in_array($action, $subresource->getExcludedActions(), true)) {
                continue;
            }

            $existingRoute = $routes->getByPath(
                str_replace(self::ASSOCIATION_PLACEHOLDER, $associationName, $entityRoutePath),
                $route->getMethods()
            );
            if ($existingRoute) {
                // move existing route before the current route
                $existingRouteName = $routes->getName($existingRoute);
                $routes->remove($existingRouteName);
                $routes->insert($existingRouteName, $existingRoute, $routeName, true);
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
                $strictRoute->setDefault(self::ENTITY_ATTRIBUTE, $entityType);
                $strictRoute->setDefault(self::ASSOCIATION_ATTRIBUTE, $associationName);
                $requirements = $strictRoute->getRequirements();
                unset($requirements[self::ENTITY_ATTRIBUTE]);
                unset($requirements[self::ASSOCIATION_ATTRIBUTE]);
                $strictRoute->setRequirements($requirements);
                $routes->insert(
                    $routes->generateRouteName($routeName),
                    $strictRoute,
                    $routeName,
                    true
                );
            }
        }
    }

    /**
     * Checks if a route has the given placeholder in a path.
     *
     * @param Route  $route
     * @param string $placeholder
     *
     * @return bool
     */
    protected function hasAttribute(Route $route, $placeholder)
    {
        return false !== strpos($route->getPath(), $placeholder);
    }
}
