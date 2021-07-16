<?php

namespace Oro\Bundle\LocaleBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/locale_data.yml" files.
 */
class LocaleDataConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'locale_data';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $treeBuilder->getRootNode()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('default_locale')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('currency_code')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('phone_prefix')
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
