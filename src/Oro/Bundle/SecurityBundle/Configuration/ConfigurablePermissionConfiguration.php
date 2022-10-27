<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/configurable_permissions.yml" files.
 */
class ConfigurablePermissionConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_configurable_permissions';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $builder->getRootNode();

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
        $treeBuilder = new TreeBuilder($nodeName);
        $rootNode = $treeBuilder->getRootNode();
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
