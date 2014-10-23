<?php

namespace Oro\Bundle\EntityPaginationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root(OroEntityPaginationExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            ['enabled' => ['type' => 'boolean', 'value' => true]]
        );

        return $treeBuilder;
    }
}
