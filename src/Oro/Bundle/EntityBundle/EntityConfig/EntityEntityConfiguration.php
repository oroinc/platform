<?php

namespace Oro\Bundle\EntityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for entity scope.
 */
class EntityEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'entity';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('icon')
                ->info('`string` sets the icon in the admin area. For more information, see ' .
                    'Font Awesome(https://fontawesome.com/v4.7.0/icons/) documentation .')
            ->end()
            ->scalarNode('label')
                ->info('`string` changes label of the entity.')
            ->end()
            ->scalarNode('plural_label')
                ->info('`string` changes plural label of the entity.')
            ->end()
            ->scalarNode('description')
                ->info('`string` changes description of the entity.')
            ->end()
            ->scalarNode('entity_alias')
                ->info('`string` stores an alias generated for an entity and helps to resolve duplicate aliases.')
            ->end()
            ->scalarNode('entity_plural_alias')
                ->info('`string` stores a plural alias generated for an entity and helps to resolve duplicate aliases.')
            ->end()
            ->arrayNode('contact_information')
                ->info('`array` enables you to change contact information (phone or email) for the entity.')
                ->prototype('variable')->end()
            ->end()
        ;
    }
}
