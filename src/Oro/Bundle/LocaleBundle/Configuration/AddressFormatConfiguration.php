<?php

namespace Oro\Bundle\LocaleBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/address_format.yml" files.
 */
class AddressFormatConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'address_format';

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
                    ->scalarNode('format')
                        ->cannotBeEmpty()
                        ->defaultValue('%name%\n%organization%\n%street%\n%CITY%\n%COUNTRY%')
                    ->end()
                    ->scalarNode('latin_format')
                        ->cannotBeEmpty()
                        ->defaultValue('%name%\n%organization%\n%street%\n%CITY%\n%COUNTRY%')
                    ->end()
                    ->arrayNode('require')
                        ->treatNullLike(array())
                        ->prototype('scalar')->end()
                        ->defaultValue(array('street', 'city'))
                    ->end()
                    ->scalarNode('zip_name_type')
                        ->cannotBeEmpty()
                        ->defaultValue('postal')
                    ->end()
                    ->scalarNode('region_name_type')
                        ->cannotBeEmpty()
                        ->defaultValue('province')
                    ->end()
                    ->scalarNode('direction')
                        ->cannotBeEmpty()
                        ->defaultValue('ltr')
                    ->end()
                    ->scalarNode('postprefix')
                        ->defaultNull()
                    ->end()
                    ->booleanNode('has_disputed')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode('format_charset')
                        ->cannotBeEmpty()
                        ->defaultValue('UTF-8')
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
