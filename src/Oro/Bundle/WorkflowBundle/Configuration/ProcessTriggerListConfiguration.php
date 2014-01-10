<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ProcessTriggerListConfiguration implements ConfigurationInterface
{
    /**
     * @var ProcessTriggerConfiguration
     */
    protected $triggerConfiguration;

    /**
     * @param ProcessTriggerConfiguration $triggerConfiguration
     */
    public function __construct(ProcessTriggerConfiguration $triggerConfiguration)
    {
        $this->triggerConfiguration = $triggerConfiguration;
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
        $this->triggerConfiguration->addTriggerNodes($rootNode->prototype('array')->prototype('array'));

        return $treeBuilder;
    }
}
