<?php

namespace Oro\Bundle\EntityPaginationBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_LIMIT = 1000;
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root(OroEntityPaginationExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'enabled' => ['type' => 'boolean', 'value' => true],
                'limit'   => ['type' => 'integer', 'value' => self::DEFAULT_LIMIT]
            ]
        );

        return $treeBuilder;
    }
}
