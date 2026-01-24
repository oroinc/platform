<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * Defines the configuration structure for a list of workflows.
 *
 * This configuration class validates and processes workflow configurations using Symfony's
 * configuration definition system, delegating individual workflow node definitions to
 * WorkflowConfiguration.
 */
class WorkflowListConfiguration implements ConfigurationInterface
{
    const NODE_WORKFLOWS = 'workflows';

    /**
     * @var WorkflowConfiguration
     */
    protected $workflowConfiguration;

    public function __construct(WorkflowConfiguration $workflowConfiguration)
    {
        $this->workflowConfiguration = $workflowConfiguration;
    }

    /**
     * Processes and validates configuration
     *
     * @param array $configs
     * @return array
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();
        return $processor->processConfiguration($this, $configs);
    }

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::NODE_WORKFLOWS);
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->useAttributeAsKey('name');
        $this->workflowConfiguration->addWorkflowNodes($rootNode->prototype('array')->children());

        return $treeBuilder;
    }
}
