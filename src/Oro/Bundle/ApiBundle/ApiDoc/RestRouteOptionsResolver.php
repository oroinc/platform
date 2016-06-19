<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

class RestRouteOptionsResolver implements RouteOptionsResolverInterface
{
    const ROUTE_GROUP = 'rest_api';

    const ENTITY_ATTRIBUTE        = 'entity';
    const ENTITY_PLACEHOLDER      = '{entity}';
    const ID_ATTRIBUTE            = 'id';
    const ID_PLACEHOLDER          = '{id}';
    const ASSOCIATION_ATTRIBUTE   = 'association';
    const ASSOCIATION_PLACEHOLDER = '{association}';

    /** @var bool */
    protected $isApplicationInstalled;

    /** @var RestDocViewDetector */
    protected $docViewDetector;

    /** @var ResourcesProvider */
    protected $resourcesProvider;

    /** @var SubresourcesProvider */
    protected $subresourcesProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var array */
    protected $resources;

    /**
     * @param bool|string|null     $isApplicationInstalled
     * @param RestDocViewDetector  $docViewDetector
     * @param ResourcesProvider    $resourcesProvider
     * @param SubresourcesProvider $subresourcesProvider
     * @param DoctrineHelper       $doctrineHelper
     * @param ValueNormalizer      $valueNormalizer
     */
    public function __construct(
        $isApplicationInstalled,
        RestDocViewDetector $docViewDetector,
        ResourcesProvider $resourcesProvider,
        SubresourcesProvider $subresourcesProvider,
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->isApplicationInstalled = !empty($isApplicationInstalled);
        $this->docViewDetector = $docViewDetector;
        $this->resourcesProvider = $resourcesProvider;
        $this->subresourcesProvider = $subresourcesProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if (!$this->isApplicationInstalled || $this->docViewDetector->getRequestType()->isEmpty()) {
            return;
        }
        if ($route->getOption('group') === 'rest_api_deprecated') {
            $routes->remove($routes->getName($route));
            return;
        }
        if ($route->getOption('group') !== self::ROUTE_GROUP) {
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
     * @param string $entityType
     *
     * @return string
     */
    protected function getEntityClass($entityType)
    {
        return ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->docViewDetector->getRequestType()
        );
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    protected function getEntityType($entityClass)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $this->docViewDetector->getRequestType()
        );
    }

    /**
     * @return ApiResource[]
     */
    protected function getResources()
    {
        if (null === $this->resources) {
            $this->resources = $this->resourcesProvider->getResources(
                $this->docViewDetector->getVersion(),
                $this->docViewDetector->getRequestType()
            );
        }

        return $this->resources;
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param ApiResource[]           $resources
     */
    protected function adjustRoutes(Route $route, RouteCollectionAccessor $routes, $resources)
    {
        $routeName = $routes->getName($route);

        $action = $route->getDefault('_action');
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            $entityType = $this->getEntityType($entityClass);
            if ($this->hasAttribute($route, self::ASSOCIATION_PLACEHOLDER)) {
                $this->addSubresources($action, $entityType, $entityClass, $routeName, $route, $routes);
            } elseif (!in_array($action, $resource->getExcludedActions(), true)) {
                $this->addResource($entityType, $entityClass, $routeName, $route, $routes);
            }
        }
    }

    /**
     * @param string                  $entityType
     * @param string                  $entityClass
     * @param string                  $routeName
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     */
    protected function addResource(
        $entityType,
        $entityClass,
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
            if ($this->hasAttribute($route, self::ID_PLACEHOLDER)) {
                $this->setIdRequirement($strictRoute, $entityClass);
            }
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
                if ($this->hasAttribute($route, self::ID_PLACEHOLDER)) {
                    $this->setIdRequirement($strictRoute, $entityClass);
                }
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
     * @param Route  $route
     * @param string $entityClass
     */
    protected function setIdRequirement(Route $route, $entityClass)
    {
        $metadata     = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $idFields     = $metadata->getIdentifierFieldNames();
        $idFieldCount = count($idFields);
        if ($idFieldCount === 1) {
            // single identifier
            $route->setRequirement(
                self::ID_ATTRIBUTE,
                $this->getIdFieldRequirement($metadata->getTypeOfField(reset($idFields)))
            );
        } elseif ($idFieldCount > 1) {
            // combined identifier
            $requirements = [];
            foreach ($idFields as $field) {
                $requirements[] = $field . '=' . $this->getIdFieldRequirement($metadata->getTypeOfField($field));
            }
            $route->setRequirement(
                self::ID_ATTRIBUTE,
                implode(',', $requirements)
            );
        }
    }

    /**
     * @param string $fieldType
     *
     * @return string
     */
    protected function getIdFieldRequirement($fieldType)
    {
        $result = $this->valueNormalizer->getRequirement($fieldType, $this->docViewDetector->getRequestType());

        if (ValueNormalizer::DEFAULT_REQUIREMENT === $result) {
            $result = '[^\.]+';
        }

        return $result;
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
