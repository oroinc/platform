<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

class PermissionListConfiguration implements ConfigurationInterface
{
    const ROOT_NODE_NAME = 'permissions';

    /**
     * @var PermissionConfiguration
     */
    protected $configuration;

    /**
     * @param PermissionConfiguration $configuration
     */
    public function __construct(PermissionConfiguration $configuration)
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

        return $processor->processConfiguration($this, $configs);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root(static::ROOT_NODE_NAME);
        $root->useAttributeAsKey('name');
        $this->configuration->addNodes($root->prototype('array'));

        return $builder;
    }
}
