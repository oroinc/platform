<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

class EntityDefinitionConfiguration extends TargetEntityDefinitionConfiguration
{
    /**
     * {@inheritdoc}
     */
    public function configureEntityNode(NodeBuilder $node)
    {
        parent::configureEntityNode($node);
        $node
            ->scalarNode(EntityDefinitionConfig::LABEL)->cannotBeEmpty()->end()
            ->scalarNode(EntityDefinitionConfig::PLURAL_LABEL)->cannotBeEmpty()->end()
            ->scalarNode(EntityDefinitionConfig::DESCRIPTION)->cannotBeEmpty()->end()
            ->arrayNode('actions')
                ->prototype('array')
                ->children()
                    ->scalarNode('delete_handler')
                    ->end()
                ->end();
    }
}
