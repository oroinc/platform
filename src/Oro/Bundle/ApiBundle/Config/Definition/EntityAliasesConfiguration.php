<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The configuration of elements in "entity_aliases" section.
 */
class EntityAliasesConfiguration
{
    /**
     * Builds the definition of a section configuration.
     *
     * @param NodeBuilder $node
     */
    public function configure(NodeBuilder $node): void
    {
        $node
            ->scalarNode('alias')
                ->info('The singular form of an entity alias')
                ->isRequired()
                ->cannotBeEmpty()
                ->validate()
                    ->ifTrue(
                        function ($v) {
                            return !\preg_match('/^[a-z][a-z0-9_]*$/D', $v);
                        }
                    )
                    ->thenInvalid(
                        'The value "%s" cannot be used as an entity alias '
                        . 'because it contains illegal characters. '
                        . 'The valid alias should start with a letter and only contain '
                        . 'lower case letters, numbers and underscores ("_").'
                    )
                ->end()
            ->end()
            ->scalarNode('plural_alias')
                ->info('The plural form of an entity alias')
                ->isRequired()
                ->cannotBeEmpty()
                ->validate()
                    ->ifTrue(
                        function ($v) {
                            return !\preg_match('/^[a-z][a-z0-9_]*$/D', $v);
                        }
                    )
                    ->thenInvalid(
                        'The value "%s" cannot be used as an entity plural alias '
                        . 'because it contains illegal characters. '
                        . 'The valid alias should start with a letter and only contain '
                        . 'lower case letters, numbers and underscores ("_").'
                    )
                ->end()
            ->end();
    }
}
