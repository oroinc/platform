<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
                    ->arrayNode('capabilities')
                        ->prototype('variable')->end()
                    ->end()
                    ->append($this->getPermissionArrayNode('entities'))
                    ->append($this->getPermissionArrayNode('workflows'))
                ->end()
            ->end();

        return $builder;
    }

    /**
     * @param string $nodeName
     *
     * @return ArrayNodeDefinition
     */
    protected function getPermissionArrayNode($nodeName)
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($nodeName);
        $rootNode
            ->prototype('variable')
                ->beforeNormalization()
                    ->always()
                    ->then(function (array $permissions) {
                        $result = [];
                        foreach ($permissions as $permission => $value) {
                            $result[strtoupper($permission)] = $value;
                        }

                        return $result;
                    })
                ->end()
            ->end();

        return $rootNode;
    }
}
