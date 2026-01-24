<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * Defines the configuration structure for a list of process definitions.
 *
 * This configuration class validates and processes process definition configurations using
 * Symfony's configuration definition system, delegating individual definition node definitions
 * to {@see ProcessDefinitionConfiguration}.
 */
class ProcessDefinitionListConfiguration implements ConfigurationInterface
{
    /**
     * @var ProcessDefinitionConfiguration
     */
    protected $definitionConfiguration;

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

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('configuration');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->useAttributeAsKey('name');
        $this->definitionConfiguration->addDefinitionNodes($rootNode->prototype('array'));

        return $treeBuilder;
    }
}
