<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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
                        ->defaultTrue()
                    ->end()
                    ->arrayNode('capabilities')
                        ->prototype('variable')
                            ->beforeNormalization()
                                ->always(function ($data) {
                                    if (!is_bool($data)) {
                                        throw new InvalidConfigurationException(
                                            'For items of node "capabilities" allowed only boolean values'
                                        );
                                    }

                                    return $data;
                                })
                            ->end()
                        ->end()
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
                    ->always(function ($data) use ($nodeName) {
                        $result = $data;
                        if (is_array($data)) {
                            $result = [];
                            foreach ($data as $permission => $value) {
                                if (!is_bool($value)) {
                                    throw new InvalidConfigurationException(
                                        sprintf(
                                            'For every permission of node "%s" can be set only boolean value',
                                            $nodeName
                                        )
                                    );
                                }
                                $result[strtoupper($permission)] = $value;
                            }
                        } elseif (!is_bool($data)) {
                            throw new InvalidConfigurationException(
                                sprintf('For node "%s" allowed only array or boolean value', $nodeName)
                            );
                        }

                        return $result;
                    })
                ->end()
            ->end();

        return $rootNode;
    }
}
