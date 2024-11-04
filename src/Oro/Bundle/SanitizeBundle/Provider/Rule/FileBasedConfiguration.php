<?php

namespace Oro\Bundle\SanitizeBundle\Provider\Rule;

use Oro\Bundle\SanitizeBundle\RuleProcessor\Entity\ProcessorsRegistry as EntityRuleProcessorRegistry;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\ProcessorsRegistry as FieldRuleProcessorRegistry;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for the configuration loaded from "Resources/config/oro/sanitize.yml" files.
 */
class FileBasedConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_sanitize';

    public function __construct(
        private FieldRuleProcessorRegistry $fieldProcessorRegistry,
        private EntityRuleProcessorRegistry $entityProcessorRegistry,
    ) {
    }

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $node = $treeBuilder->getRootNode()->children();

        $this->appendRawSqls($node);
        $this->appendEntityRules($node);

        return $treeBuilder;
    }

    private function appendRawSqls(NodeBuilder $builder): void
    {
        $builder
            ->arrayNode('raw_sqls')
                ->info('Defines a list of raw sanitizing SQLs that are not bound to an entity directly')
                ->scalarPrototype()->end()
                ->defaultValue([]);
    }

    private function appendEntityRules(NodeBuilder $builder): void
    {
        $children = $builder
            ->arrayNode('entity')
                ->arrayPrototype()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($value) {
                            return ['rule' => $value];
                        })
                    ->end()
                    ->validate()
                        ->always(function ($value) {
                            if (empty($value['rule']) && empty($value['raw_sqls']) && empty($value['fields'])) {
                                throw new \RuntimeException(
                                    "At least one of the following options must be pointed:"
                                    . " 'rule', 'raw_sqls', 'fields'"
                                );
                            }

                            return $value;
                        })
                    ->end()
                    ->children()
                        ->enumNode('rule')
                            ->info('Defines a sanitizing rule for an entity')
                            ->values(array_merge([null, ''], $this->entityProcessorRegistry->getProcessorAliases()))
                            ->defaultNull()
                        ->end()
                        ->arrayNode('rule_options')
                            ->prototype('variable')->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('raw_sqls')
                            ->info('Defines a list of SQL commands for sanitizing raw data related to an entity')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end();

        $this->appendFieldRules($children);
    }

    private function appendFieldRules(NodeBuilder $builder): void
    {
        $builder
            ->arrayNode('fields')
                ->arrayPrototype()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($value) {
                            return ['rule' => $value];
                        })
                    ->end()
                    ->validate()
                        ->always(function ($value) {
                            if (empty($value['rule']) && empty($value['raw_sqls'])) {
                                throw new \RuntimeException(
                                    "At least one of the following options must be pointed: 'rule', 'raw_sqls'"
                                );
                            }

                            return $value;
                        })
                    ->end()
                    ->children()
                        ->enumNode('rule')
                            ->info('Defines a sanitizing rule for a field')
                            ->values(array_merge([null, ''], $this->fieldProcessorRegistry->getProcessorAliases()))
                            ->defaultNull()
                        ->end()
                        ->arrayNode('rule_options')
                            ->info('Defines a list of sanitizing rule options for a field')
                            ->prototype('variable')->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('raw_sqls')
                            ->info('Defines a list of raw sanitizing SQLs for a field')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
                ->defaultValue([]);
    }
}
