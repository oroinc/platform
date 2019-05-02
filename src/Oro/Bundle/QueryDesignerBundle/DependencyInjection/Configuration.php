<?php

namespace Oro\Bundle\QueryDesignerBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const CONDITIONS_GROUP_MERGE_SAME_ENTITY_CONDITIONS = 'conditions_group_merge_same_entity_conditions';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_query_designer');

        SettingsBuilder::append(
            $rootNode,
            [
                self::CONDITIONS_GROUP_MERGE_SAME_ENTITY_CONDITIONS => ['type' => 'boolean', 'value' => true]
            ]
        );

        return $treeBuilder;
    }
}
