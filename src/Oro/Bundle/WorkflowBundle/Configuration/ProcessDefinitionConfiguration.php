<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * Defines the configuration of workflow process definitions.
 */
class ProcessDefinitionConfiguration extends AbstractConfiguration implements ConfigurationInterface
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
        $treeBuilder = new TreeBuilder('configuration');
        $rootNode = $treeBuilder->getRootNode();
        $this->addDefinitionNodes($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $nodeDefinition
     * @return ArrayNodeDefinition
     */
    public function addDefinitionNodes(ArrayNodeDefinition $nodeDefinition)
    {
        $nodeDefinition
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
                ->arrayNode('exclude_definitions')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('preconditions')
                    ->prototype('variable')
                    ->end()
                ->end()
                ->arrayNode('actions_configuration')
                    ->prototype('variable')
                    ->end()
                ->end()
            ->end();

        return $nodeDefinition;
    }
}
