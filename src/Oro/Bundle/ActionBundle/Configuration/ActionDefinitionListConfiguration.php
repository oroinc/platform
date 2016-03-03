<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ActionDefinitionListConfiguration implements ConfigurationInterface
{
    /**
     * @var ActionDefinitionConfiguration
     */
    protected $configuration;

    /**
     * @param ActionDefinitionConfiguration $configuration
     */
    public function __construct(ActionDefinitionConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param array $configs
     * @return array
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();
        return $processor->processConfiguration($this, [$configs]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('actions');
        $root->useAttributeAsKey('name');
        $this->configuration->addNodes($root->prototype('array'));

        return $builder;
    }
}
