<?php

namespace Oro\Bundle\EntityBundle\Routing;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;

class DictionaryEntityRouteOptionsResolver implements RouteOptionsResolverInterface
{
    const ROUTE_GROUP = 'dictionary_entity';
    const ENTITY_ATTRIBUTE = 'dictionary';
    const ENTITY_PLACEHOLDER = '{dictionary}';

    /** @var ChainDictionaryValueListProvider */
    private $dictionaryProvider;

    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $supportedEntities;

    /**
     * @param ChainDictionaryValueListProvider $dictionaryProvider
     * @param EntityAliasResolver              $entityAliasResolver
     * @param LoggerInterface                  $logger
     */
    public function __construct(
        ChainDictionaryValueListProvider $dictionaryProvider,
        EntityAliasResolver $entityAliasResolver,
        LoggerInterface $logger
    ) {
        $this->dictionaryProvider = $dictionaryProvider;
        $this->entityAliasResolver = $entityAliasResolver;
        $this->logger = $logger;
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
            $entities = $this->getSupportedEntities();
            if (!empty($entities)) {
                $this->adjustRoutes($route, $routes, $entities);
            }
            $route->setRequirement(self::ENTITY_ATTRIBUTE, '\w+');

            $route->setOption('hidden', true);
        }
    }

    /**
     * @return string[] The list of entity plural aliases
     */
    private function getSupportedEntities()
    {
        if (null === $this->supportedEntities) {
            $entities = $this->dictionaryProvider->getSupportedEntityClasses();

            $this->supportedEntities = [];
            foreach ($entities as $className) {
                try {
                    $this->supportedEntities[] = $this->entityAliasResolver->getPluralAlias($className);
                } catch (EntityAliasNotFoundException $e) {
                    $this->logger->error(
                        'Cannot get an alias for the entity "{entity}"',
                        ['exception' => $e, 'entity' => $className]
                    );
                }
            }
        }

        return $this->supportedEntities;
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param string[]                $entities The list of entity plural aliases
     */
    private function adjustRoutes(Route $route, RouteCollectionAccessor $routes, $entities)
    {
        $routeName = $routes->getName($route);

        foreach ($entities as $pluralAlias) {
            $existingRoute = $routes->getByPath(
                str_replace(self::ENTITY_PLACEHOLDER, $pluralAlias, $route->getPath()),
                $route->getMethods()
            );
            if ($existingRoute) {
                // move existing route before the current route
                $existingRouteName = $routes->getName($existingRoute);
                $routes->insert($existingRouteName, $existingRoute, $routeName, true);
            } else {
                // add an additional strict route based on the base route and current entity
                $strictRoute = $routes->cloneRoute($route);
                $strictRoute->setPath(str_replace(self::ENTITY_PLACEHOLDER, $pluralAlias, $strictRoute->getPath()));
                $strictRoute->setDefault(self::ENTITY_ATTRIBUTE, $pluralAlias);
                $routes->insert($routes->generateRouteName($routeName), $strictRoute, $routeName, true);
            }
        }
    }

    /**
     * Checks if a route has the given attribute
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
}
