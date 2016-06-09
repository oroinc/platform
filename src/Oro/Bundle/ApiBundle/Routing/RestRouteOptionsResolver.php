<?php

namespace Oro\Bundle\ApiBundle\Routing;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Oro\Bundle\ApiBundle\Provider\ResourcesLoader;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class RestRouteOptionsResolver implements RouteOptionsResolverInterface
{
    const ROUTE_GROUP        = 'rest_api';
    const ENTITY_ATTRIBUTE   = 'entity';
    const ENTITY_PLACEHOLDER = '{entity}';
    const ID_ATTRIBUTE       = 'id';
    const ID_PLACEHOLDER     = '{id}';

    /** @var bool */
    protected $isApplicationInstalled;

    /** @var ResourcesLoader */
    protected $resourcesLoader;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var RequestType */
    protected $requestType;

    /** @var array */
    private $supportedEntities;

    /**
     * @param bool|string|null $isApplicationInstalled
     * @param ResourcesLoader  $resourcesLoader
     * @param DoctrineHelper   $doctrineHelper
     * @param ValueNormalizer  $valueNormalizer
     */
    public function __construct(
        $isApplicationInstalled,
        ResourcesLoader $resourcesLoader,
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->isApplicationInstalled = !empty($isApplicationInstalled);
        $this->resourcesLoader        = $resourcesLoader;
        $this->doctrineHelper         = $doctrineHelper;
        $this->valueNormalizer        = $valueNormalizer;
        $this->requestType            = new RequestType([RequestType::REST, RequestType::JSON_API]);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if (!$this->isApplicationInstalled) {
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
            $entities = $this->getSupportedEntities();
            if (!empty($entities)) {
                $this->adjustRoutes($route, $routes, $entities);
            }
            $route->setRequirement(self::ENTITY_ATTRIBUTE, '\w+');

            $route->setOption('hidden', true);
        }
    }

    /**
     * @return array [[entity class, entity type, [excluded action, ...]], ...]
     */
    protected function getSupportedEntities()
    {
        if (null === $this->supportedEntities) {
            $resources = $this->resourcesLoader->getResources(Version::LATEST, $this->requestType);

            $this->supportedEntities = [];
            foreach ($resources as $resource) {
                $className = $resource->getEntityClass();
                $entityType = $this->valueNormalizer->normalizeValue(
                    $className,
                    DataType::ENTITY_TYPE,
                    $this->requestType
                );
                if (!empty($entityType)) {
                    $this->supportedEntities[] = [
                        $className,
                        $entityType,
                        $resource->getExcludedActions()
                    ];
                }
            }
        }

        return $this->supportedEntities;
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param array                   $entities [[entity class, entity type, [excluded action, ...]], ...]
     */
    protected function adjustRoutes(Route $route, RouteCollectionAccessor $routes, $entities)
    {
        $routeName = $routes->getName($route);

        $action = $route->getDefault('_action');
        foreach ($entities as $entity) {
            list($className, $entityType, $excludedActions) = $entity;

            // check if given action is excluded for the entity
            if (in_array($action, $excludedActions, true)) {
                continue;
            }

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
                    $this->setIdRequirement($strictRoute, $className);
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
        $result = $this->valueNormalizer->getRequirement($fieldType, $this->requestType);

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
