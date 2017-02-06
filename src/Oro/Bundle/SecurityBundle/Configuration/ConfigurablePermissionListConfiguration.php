<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurablePermissionListConfiguration implements PermissionConfigurationInterface
{
    const ROOT_NODE_NAME = 'oro_configurable_permissions';

    /**
     * {@inheritdoc}
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration($this, $configs);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $rootNode = $builder->root(static::ROOT_NODE_NAME);

        $rootNode->useAttributeAsKey('name')
            ->isRequired()
            ->prototype('array')
                ->children()
                    ->booleanNode('default')
                        ->defaultFalse()
                    ->end()
                    ->arrayNode('entities')
                        ->useAttributeAsKey('name')
                        ->prototype('variable')->end()
                    ->end()
                    ->arrayNode('capabilities')
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
