<?php

namespace Oro\Bundle\ActivityBundle\Routing;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class ActivityRelationRouteOptionsResolver implements RouteOptionsResolverInterface
{
    const ACTIVITY_ATTRIBUTE = 'activity';
    const ACTIVITY_PLACEHOLDER = '{activity}';
    const ACTIVITY_ID_ATTRIBUTE = 'id';
    const ACTIVITY_ID_PLACEHOLDER = '{id}';
    const ENTITY_ATTRIBUTE = 'entity';
    const ENTITY_PLACEHOLDER = '{entity}';
    const ENTITY_ID_ATTRIBUTE = 'entityId';
    const ENTITY_ID_PLACEHOLDER = '{entityId}';

    /** @var ConfigProvider */
    protected $groupingConfigProvider;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param ConfigProvider      $groupingConfigProvider
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(ConfigProvider $groupingConfigProvider, EntityAliasResolver $entityAliasResolver)
    {
        $this->groupingConfigProvider = $groupingConfigProvider;
        $this->entityAliasResolver    = $entityAliasResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routeCollectionAccessor)
    {
        if ($route->getOption('group') !== 'activity_relations'
            || !$this->hasAttribute($route, self::ACTIVITY_PLACEHOLDER)
        ) {
            return;
        }

        $activities = array_map(
            function (ConfigInterface $config) {
                // convert to entity alias
                return $this->entityAliasResolver->getPluralAlias(
                    $config->getId()->getClassName()
                );
            },
            $this->groupingConfigProvider->filter(
                function (ConfigInterface $config) {
                    // filter activity entities
                    $groups = $config->get('groups');

                    return
                        !empty($groups)
                        && in_array(ActivityScope::GROUP_ACTIVITY, $groups, true);
                }
            )
        );

        $this->adjustRoutes($route, $routeCollectionAccessor, $activities);

        $route->setRequirement(self::ACTIVITY_ATTRIBUTE, implode('|', $activities));
        $this->completeRouteRequirements($route);
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routeCollectionAccessor
     * @param string[]                $activities
     */
    protected function adjustRoutes(
        Route $route,
        RouteCollectionAccessor $routeCollectionAccessor,
        $activities
    ) {
        $routeName = $routeCollectionAccessor->getName($route);

        foreach ($activities as $activity) {
            $existingRoute = $routeCollectionAccessor->getByPath(
                str_replace(self::ACTIVITY_PLACEHOLDER, $activity, $route->getPath()),
                $route->getMethods()
            );
            if ($existingRoute) {
                // move existing route before the current route
                $existingRouteName = $routeCollectionAccessor->getName($existingRoute);
                $routeCollectionAccessor->remove($existingRouteName);
                $routeCollectionAccessor->insert(
                    $existingRouteName,
                    $existingRoute,
                    $routeName,
                    true
                );
            } else {
                // add an additional strict route based on the base route and current activity
                $strictRoute = $routeCollectionAccessor->cloneRoute($route);
                $strictRoute->setPath(str_replace(self::ACTIVITY_PLACEHOLDER, $activity, $strictRoute->getPath()));
                $strictRoute->setDefault(self::ACTIVITY_ATTRIBUTE, $activity);
                $this->completeRouteRequirements($strictRoute);
                $routeCollectionAccessor->insert(
                    $routeCollectionAccessor->generateRouteName($routeName),
                    $strictRoute,
                    $routeName,
                    true
                );
            }
        }
    }

    /**
     * Adds not filled requirements for the given route
     *
     * @param Route $route
     */
    protected function completeRouteRequirements(Route $route)
    {
        if (null === $route->getRequirement(self::ACTIVITY_ID_ATTRIBUTE)
            && $this->hasAttribute($route, self::ACTIVITY_ID_PLACEHOLDER)
        ) {
            $route->setRequirement(self::ACTIVITY_ID_ATTRIBUTE, '\d+');
        }
        if (null === $route->getRequirement(self::ENTITY_ATTRIBUTE)
            && $this->hasAttribute($route, self::ENTITY_PLACEHOLDER)
        ) {
            $route->setRequirement(self::ENTITY_ATTRIBUTE, '\w+');
        }
        if (null === $route->getRequirement(self::ENTITY_ID_ATTRIBUTE)
            && $this->hasAttribute($route, self::ENTITY_ID_PLACEHOLDER)
        ) {
            $route->setRequirement(self::ENTITY_ID_ATTRIBUTE, '[^/]+');
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
    protected function hasAttribute(Route $route, $placeholder)
    {
        return false !== strpos($route->getPath(), $placeholder);
    }
}
