<?php

namespace Oro\Bundle\LocaleBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/name_format.yml" files.
 */
class NameFormatConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'name_format';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root(self::ROOT_NODE)
            ->useAttributeAsKey('name')
            ->prototype('scalar')->end();

        return $treeBuilder;
    }
}
