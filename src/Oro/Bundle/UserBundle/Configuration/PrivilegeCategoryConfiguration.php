<?php

namespace Oro\Bundle\UserBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/acl_categories.yml" files.
 */
class PrivilegeCategoryConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'acl_categories';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $treeBuilder->getRootNode()
            ->info('Configuration of ACL categories.')
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('label')
                        ->info('The label of a category.')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->booleanNode('tab')
                        ->info('Indicates whether a category should be represented as a tab.')
                        ->defaultValue(false)
                    ->end()
                    ->integerNode('priority')
                        ->info('The priority of a category. Categories with a smaller priority number are shown first.')
                        ->defaultValue(0)
                    ->end()
                    ->booleanNode('visible')
                        ->info('Indicates whether a category is visible or not.')
                        ->defaultValue(true)
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
