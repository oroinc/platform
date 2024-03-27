<?php

namespace Oro\Bundle\SanitizeBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Entity\ProcessorsRegistry;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Validates the configuration of an entity responsible for generating of sanitizing SQL.
 */
class SanitizeEntityConfiguration implements EntityConfigInterface
{
    public function __construct(private ProcessorsRegistry $entityProcessorRegistry)
    {
    }

    public function getSectionName(): string
    {
        return 'sanitize';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->arrayNode('raw_sqls')
                ->info('Defines a list of raw sanitizing SQLs for an entity')
                ->scalarPrototype()->end()
                ->defaultValue([])
            ->end()
            ->enumNode('rule')
                ->info('Defines sanitizing rule for an entity')
                ->values(array_merge([null, ''], $this->entityProcessorRegistry->getProcessorAliases()))
                ->defaultNull()
            ->end()
            ->arrayNode('rule_options')
                ->info('Defines a list of sanitizing rule options for an entity')
                    ->scalarPrototype()->end()
                ->defaultValue([])
            ->end()
        ;
    }
}
