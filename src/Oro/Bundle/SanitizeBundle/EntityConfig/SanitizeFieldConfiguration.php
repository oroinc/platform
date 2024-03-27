<?php

namespace Oro\Bundle\SanitizeBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\ProcessorsRegistry;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Validates the configuration of an entity field responsible for generating of sanitizing SQL.
 */
class SanitizeFieldConfiguration implements FieldConfigInterface
{
    public function __construct(private ProcessorsRegistry $fieldProcessorRegistry)
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
                ->info('Defines a list of SQL statements used for raw sanitization of a field')
                ->scalarPrototype()->end()
                ->defaultValue([])
            ->end()
            ->enumNode('rule')
                ->info('Defines sanitizing rule for a field')
                ->values(array_merge([null, ''], $this->fieldProcessorRegistry->getProcessorAliases()))
                ->defaultNull()
            ->end()
            ->arrayNode('rule_options')
                ->info('Defines a list of sanitizing rule options for a field')
                    ->scalarPrototype()->end()
                ->defaultValue([])
            ->end()
        ;
    }
}
