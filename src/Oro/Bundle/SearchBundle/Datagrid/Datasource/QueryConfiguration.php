<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Datasource;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Defines the configuration structure for search query datasources.
 *
 * This class implements the Symfony {@see ConfigurationInterface} to provide a tree builder
 * that validates and normalizes search query configuration. It defines the expected
 * structure for query configuration including select fields, from entities, and where
 * conditions with support for both `AND` and `OR` logical operators.
 */
class QueryConfiguration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('query');

        $builder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('select')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('from')
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('where')
                    ->append($this->addWhereNode('and'))
                    ->append($this->addWhereNode('or'))
                ->end()
            ->end();

        return $builder;
    }

    /**
     * @param  string $name Where type ('and', 'or')
     *
     * @throws InvalidConfigurationException
     * @return ArrayNodeDefinition
     */
    protected function addWhereNode($name)
    {
        if (!in_array($name, ['and', 'or'], true)) {
            throw new InvalidConfigurationException(sprintf('Invalid where type "%s"', $name));
        }

        $builder = new TreeBuilder($name);

        return $builder->getRootNode()
            ->requiresAtLeastOneElement()
            ->prototype('scalar')->end();
    }
}
