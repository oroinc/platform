<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ProcessDefinitionConfiguration implements ConfigurationInterface
{
    /**
     * @param array $configs
     * @return array
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();
        return $processor->processConfiguration($this, array($configs));
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('configuration');
        $rootNode
            ->children()
                ->scalarNode('name')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('label')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('enabled')
                    ->defaultTrue()
                ->end()
                ->scalarNode('entity')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('order')
                    ->defaultValue(0)
                ->end()
                ->booleanNode('execution_required')
                    ->defaultFalse()
                ->end()
                ->arrayNode('actions_configuration')
                    ->prototype('variable')
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
