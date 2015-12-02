<?php

namespace Oro\Bundle\ApiBundle\Routing;

use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\RestRequest;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag;

class RestJsonApiRouteOptionsResolver implements RouteOptionsResolverInterface
{
    const ROUTE_GROUP         = 'rest_json_api';
    const ENTITY_ATTRIBUTE    = 'entity';
    const ENTITY_PLACEHOLDER  = '{entity}';
    const ID_ATTRIBUTE        = 'id';
    const ID_PLACEHOLDER      = '{id}';
    const FORMAT_ATTRIBUTE    = '_format';

    /** @var EntityManagerBag */
    protected $entityManagerBag;

    /** @var ExclusionProviderInterface */
    protected $entityExclusionProvider;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var string[] */
    protected $formats;

    /** @var string[] */
    protected $defaultFormat;

    /**
     * @param EntityManagerBag           $entityManagerBag
     * @param ExclusionProviderInterface $entityExclusionProvider
     * @param EntityAliasResolver        $entityAliasResolver
     * @param DoctrineHelper             $doctrineHelper
     * @param ValueNormalizer            $valueNormalizer
     * @param string                     $formats
     * @param string                     $defaultFormat
     */
    public function __construct(
        EntityManagerBag $entityManagerBag,
        ExclusionProviderInterface $entityExclusionProvider,
        EntityAliasResolver $entityAliasResolver,
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer,
        $formats,
        $defaultFormat
    ) {
        $this->entityManagerBag        = $entityManagerBag;
        $this->entityExclusionProvider = $entityExclusionProvider;
        $this->entityAliasResolver     = $entityAliasResolver;
        $this->doctrineHelper          = $doctrineHelper;
        $this->valueNormalizer         = $valueNormalizer;
        $this->formats                 = $formats;
        $this->defaultFormat           = $defaultFormat;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if ($route->getOption('group') !== self::ROUTE_GROUP) {
            return;
        }

        if ($this->hasAttribute($route, self::ENTITY_PLACEHOLDER)) {
            $this->setFormatAttribute($route);

            $entities = $this->getSupportedEntityClasses();

            if (!empty($entities)) {
                $this->adjustRoutes($route, $routes, $entities);
            }
        }
    }

    /**
     * @return string[]
     */
    protected function getSupportedEntityClasses()
    {
        $entities       = [];
        $entityManagers = $this->entityManagerBag->getEntityManagers();
        foreach ($entityManagers as $em) {
            /** @var ClassMetadata[] $allMetadata */
            $allMetadata = $em->getMetadataFactory()->getAllMetadata();
            foreach ($allMetadata as $metadata) {
                if ($metadata->isMappedSuperclass) {
                    continue;
                }
                if ($this->entityExclusionProvider->isIgnoredEntity($metadata->name)) {
                    continue;
                }
                $entities[] = $metadata->name;
            }
        }

        return $entities;
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param string[]                $entities
     */
    protected function adjustRoutes(Route $route, RouteCollectionAccessor $routes, $entities)
    {
        $routeName = $routes->getName($route);

        foreach ($entities as $className) {
            $entity = $this->entityAliasResolver->getPluralAlias($className);
            if (empty($entity)) {
                continue;
            }

            $existingRoute = $routes->getByPath(
                str_replace(self::ENTITY_PLACEHOLDER, $entity, $route->getPath()),
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
                $strictRoute->setPath(str_replace(self::ENTITY_PLACEHOLDER, $entity, $strictRoute->getPath()));
                $strictRoute->setDefault(self::ENTITY_ATTRIBUTE, $entity);
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
     * @param Route $route
     */
    protected function setFormatAttribute(Route $route)
    {
        $route->setRequirement(self::FORMAT_ATTRIBUTE, $this->formats);
        $route->setDefault(self::FORMAT_ATTRIBUTE, $this->defaultFormat);
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
                $this->valueNormalizer->getRequirement(
                    $metadata->getTypeOfField(reset($idFields)),
                    [RequestType::REST, RequestType::JSON_API]
                )
            );
        } elseif ($idFieldCount > 1) {
            // combined identifier
            $requirements = [];
            foreach ($idFields as $field) {
                $requirements[] = $field . '='
                    . $this->valueNormalizer->getRequirement(
                        $metadata->getTypeOfField($field),
                        [RequestType::REST, RequestType::JSON_API]
                    );
            }
            $route->setRequirement(
                self::ID_ATTRIBUTE,
                implode(RestRequest::ARRAY_DELIMITER, $requirements)
            );
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
