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
        if ($route->getOption('group') !== 'activity_associations'
            || false === strpos($route->getPath(), '{entity}')
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

        $route->setRequirement('entity', implode('|', $activities));

        $this->adjustExistingRoutes($route, $routeCollectionAccessor, $activities);
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routeCollectionAccessor
     * @param string[]                $activities
     */
    protected function adjustExistingRoutes(
        Route $route,
        RouteCollectionAccessor $routeCollectionAccessor,
        $activities
    ) {
        // find already declared routes with the same path and method as the current route
        // and increase priority of such routes
        // this allows to override auto-generated routes
        foreach ($activities as $activity) {
            $existingRoute = $routeCollectionAccessor->findRouteByPath(
                str_replace('{entity}', $activity, $route->getPath()),
                $route->getMethods()
            );
            if ($existingRoute) {
                $existingPriority = $existingRoute->getOption('priority') ?: 0;
                $priority         = $route->getOption('priority') ?: 0;
                if ($priority <= $existingPriority) {
                    $existingRoute->setOption('priority', $priority - 1);
                }
            }

        }
    }
}
