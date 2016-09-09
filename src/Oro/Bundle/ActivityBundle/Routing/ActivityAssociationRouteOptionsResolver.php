<?php

namespace Oro\Bundle\ActivityBundle\Routing;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class ActivityAssociationRouteOptionsResolver implements RouteOptionsResolverInterface
{
    const ROUTE_GROUP = 'activity_association';
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

    /** @var array */
    private $supportedActivities;

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
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if ($route->getOption('group') !== self::ROUTE_GROUP) {
            return;
        }

        if ($this->hasAttribute($route, self::ACTIVITY_PLACEHOLDER)) {
            $activities = $this->getSupportedActivities();
            if (!empty($activities)) {
                $this->adjustRoutes($route, $routes, $activities);
            }

            $this->completeRouteRequirements($route);
            $route->setOption('hidden', true);
        } elseif ($this->hasAttribute($route, self::ENTITY_PLACEHOLDER)) {
            $this->completeRouteRequirements($route);
        }
    }

    /**
     * @return string[]
     */
    protected function getSupportedActivities()
    {
        if (null === $this->supportedActivities) {
            $this->supportedActivities = array_map(
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
        }

        return $this->supportedActivities;
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param string[]                $activities
     */
    protected function adjustRoutes(Route $route, RouteCollectionAccessor $routes, $activities)
    {
        $routeName = $routes->getName($route);

        foreach ($activities as $activity) {
            $existingRoute = $routes->getByPath(
                str_replace(self::ACTIVITY_PLACEHOLDER, $activity, $route->getPath()),
                $route->getMethods()
            );
            if ($existingRoute) {
                // move existing route before the current route
                $routes->insert($routes->getName($existingRoute), $existingRoute, $routeName, true);
            } else {
                // add an additional strict route based on the base route and current activity
                $strictRoute = $routes->cloneRoute($route);
                $strictRoute->setPath(str_replace(self::ACTIVITY_PLACEHOLDER, $activity, $strictRoute->getPath()));
                $strictRoute->setDefault(self::ACTIVITY_ATTRIBUTE, $activity);
                $this->completeRouteRequirements($strictRoute);
                $routes->insert($routes->generateRouteName($routeName), $strictRoute, $routeName, true);
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
        if (null === $route->getRequirement(self::ACTIVITY_ATTRIBUTE)
            && $this->hasAttribute($route, self::ACTIVITY_PLACEHOLDER)
        ) {
            $route->setRequirement(self::ACTIVITY_ATTRIBUTE, '\w+');
        }
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
