<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Oro\Bundle\ApiBundle\Config\Extension\AbstractConfigExtension;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class TestConfigExtension extends AbstractConfigExtension
{
    #[\Override]
    public function getEntityConfigurationSections(): array
    {
        return ['test_section' => new TestConfiguration()];
    }

    #[\Override]
    public function getConfigureCallbacks(): array
    {
        return [
            'entities.entity'       => function (NodeBuilder $node) {
                $node->scalarNode('entity_extra');
            },
            'entities.entity.field' => function (NodeBuilder $node) {
                $node->scalarNode('field_extra');
            },
            'filters'               => function (NodeBuilder $node) {
                $node->scalarNode('filters_extra');
            },
            'filters.field'         => function (NodeBuilder $node) {
                $node->scalarNode('filter_field_extra');
            },
            'sorters'               => function (NodeBuilder $node) {
                $node->scalarNode('sorters_extra');
            },
            'sorters.field'         => function (NodeBuilder $node) {
                $node->scalarNode('sorter_field_extra');
            }
        ];
    }

    #[\Override]
    public function getPostProcessCallbacks(): array
    {
        return [
            'entities.entity'       => function (array $config) {
                if (!empty($config['entity_extra'])) {
                    $config['entity_extra'] .= ' (added by extension)';
                }

                return $config;
            },
            'entities.entity.field' => function (array $config) {
                if (!empty($config['field_extra'])) {
                    $config['field_extra'] .= ' (added by extension)';
                }

                return $config;
            },
            'filters'               => function (array $config) {
                if (!empty($config['filters_extra'])) {
                    $config['filters_extra'] .= ' (added by extension)';
                }

                return $config;
            },
            'filters.field'         => function (array $config) {
                if (!empty($config['filter_field_extra'])) {
                    $config['filter_field_extra'] .= ' (added by extension)';
                }

                return $config;
            },
            'sorters'               => function (array $config) {
                if (!empty($config['sorters_extra'])) {
                    $config['sorters_extra'] .= ' (added by extension)';
                }

                return $config;
            },
            'sorters.field'         => function (array $config) {
                if (!empty($config['sorter_field_extra'])) {
                    $config['sorter_field_extra'] .= ' (added by extension)';
                }

                return $config;
            }
        ];
    }

    #[\Override]
    public function getEntityConfigurationLoaders(): array
    {
        return ['test_section' => new TestConfigLoader()];
    }
}
