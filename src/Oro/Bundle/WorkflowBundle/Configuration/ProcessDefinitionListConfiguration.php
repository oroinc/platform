<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ProcessDefinitionListConfiguration implements ConfigurationInterface
{
    /**
     * @var ProcessDefinitionConfiguration
     */
    protected $definitionConfiguration;

    /**
     * @param ProcessDefinitionConfiguration $definitionConfiguration
     */
    public function __construct(ProcessDefinitionConfiguration $definitionConfiguration)
    {
        $this->definitionConfiguration = $definitionConfiguration;
    }

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
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('configuration');
        $rootNode->useAttributeAsKey('name');
        $this->definitionConfiguration->addDefinitionNodes($rootNode->prototype('array'));

        return $treeBuilder;
    }
}
