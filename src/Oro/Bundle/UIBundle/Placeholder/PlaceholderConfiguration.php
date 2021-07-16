<?php

namespace Oro\Bundle\UIBundle\Placeholder;

use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines a schema of "Resources/config/oro/placeholders.yml" files.
 */
class PlaceholderConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE         = 'placeholders';
    public const PLACEHOLDERS_NODE = 'placeholders';
    public const ITEMS_NODE        = 'items';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $node = $rootNode->children();
        $this->appendPlaceholders($node);
        $this->appendItems($node);

        return $treeBuilder;
    }

    /**
     * Builds the configuration tree for placeholders
     */
    protected function appendPlaceholders(NodeBuilder $builder)
    {
        $children = $builder
            ->arrayNode(self::PLACEHOLDERS_NODE)
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->arrayNode(self::ITEMS_NODE)
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children();

        $children
            ->booleanNode('remove')
                ->validate()
                    // keep the 'remove' attribute only if its value is TRUE
                    ->ifTrue(function ($v) {
                        return isset($v) && !$v;
                    })
                    ->thenUnset()
                ->end()
            ->end()
            ->integerNode('order')
                ->defaultValue(0)
            ->end();

        $children->end()
            ->validate()
                // remove all items with remove=TRUE
                ->ifTrue(function ($v) {
                    return (isset($v['remove']) && $v['remove']);
                })
                ->thenUnset()
            ->end();

        $children->end()->end()
            ->validate()
                // sort items by 'order' attribute and return names of items
                ->always(function ($v) {
                    ArrayUtil::sortBy($v, false, 'order');

                    return array_keys($v);
                })
            ->end();
    }

    /**
     * Builds the configuration tree for placeholder items
     */
    protected function appendItems(NodeBuilder $builder)
    {
        $children = $builder
            ->arrayNode(self::ITEMS_NODE)
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children();

        $children
            ->variableNode('applicable')->end()
            ->variableNode('acl')
                ->beforeNormalization()
                    ->ifArray()
                    ->then(function ($v) {
                        return count($v) === 1 ? $v[0] : $v;
                    })
                ->end()
                ->validate()
                    ->ifTrue(function ($v) {
                        return null !== $v && !is_string($v) && !is_array($v);
                    })
                    ->thenInvalid('The "acl" must be a string or array, given %s.')
                ->end()
                ->validate()
                    ->ifTrue(
                        function ($v) {
                            return empty($v);
                        }
                    )
                    ->thenUnset()
                ->end()
            ->end()
            ->scalarNode('action')->end()
            ->scalarNode('template')->end()
            ->variableNode('data')->end();

        $children->end()
            ->validate()
                // remove all items if neither 'action' nor 'template' attribute is not specified
                ->ifTrue(function ($v) {
                    return (empty($v['action']) && empty($v['template']));
                })
                ->thenUnset()
            ->end()
            ->validate()
                // both 'action' and 'template' attributes should not be specified
                ->ifTrue(function ($v) {
                    return !empty($v['action']) && !empty($v['template']);
                })
                ->thenInvalid('Only one either "action" or "template" attribute can be defined. %s')
            ->end();
    }
}
