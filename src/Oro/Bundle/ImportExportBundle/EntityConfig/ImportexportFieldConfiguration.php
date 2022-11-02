<?php

namespace Oro\Bundle\ImportExportBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for importexport scope.
 */
class ImportexportFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'importexport';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('header')
                ->info('`string` sets a custom field header. By default, field label is used.')
            ->end()
            ->scalarNode('order')
                ->info('`integer` used to configure a custom column order.')
            ->end()
            ->scalarNode('identity')
                ->info('`boolean` fields with this option are used to identify (search) the entity. You can use ' .
                'multiple identity fields for one entity.')
            ->end()
            ->node('excluded', 'normalized_boolean')
                ->info('`boolean` fields with this option cannot be exported.')
            ->end()
            ->node('full', 'normalized_boolean')
                ->info('`boolean` all related entity fields’ are exported. Fields with the excluded option are ' .
                'skipped. If the option is set to false (the default value), only fields with an ' .
                'identity are exported.')
            ->end()
            ->node('process_as_scalar', 'normalized_boolean')
                ->info('`boolean` defines whether a relation field is processed as scalar value when exporting data.')
                ->defaultFalse()
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` is used to prohibit changing the import/export association state (regardless ' .
                    'of whether it is enabled or not) for the entity. ' .
                    'If TRUE, than the current state cannot be changed.')
                ->defaultFalse()
            ->end()
            ->scalarNode('fallback_field')
                ->info('`string` attribute name of related localized fallback entities.')
            ->end()
            ->arrayNode('short')
                ->info('`boolean` related entities with this option will be exported if they contain identity fields.')
            ->end()
        ;
    }
}
