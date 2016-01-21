<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class PermissionDefinitionListConfiguration implements ConfigurationInterface
{
    /**
     * @var PermissionDefinitionConfiguration
     */
    protected $configuration;

    /**
     * @param PermissionDefinitionConfiguration $configuration
     */
    public function __construct(PermissionDefinitionConfiguration $configuration)
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
        $root = $builder->root(PermissionConfigurationProvider::ROOT_NODE_NAME);
        $root->useAttributeAsKey('name');
        $this->configuration->addNodes($root->prototype('array'));

        return $builder;
    }
}
