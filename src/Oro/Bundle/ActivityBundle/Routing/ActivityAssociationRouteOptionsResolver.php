<?php

namespace Oro\Bundle\ActivityBundle\Routing;

use Symfony\Component\Routing\Route;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\DistributionBundle\Routing\RouteOptionsResolverInterface;
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
    public function resolve(Route $route)
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
    }
}
