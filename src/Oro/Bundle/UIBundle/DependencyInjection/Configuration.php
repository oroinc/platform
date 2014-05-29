<?php

namespace Oro\Bundle\UIBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see
 * {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_ui');

        $rootNode->children()
            ->booleanNode('show_pin_button_on_start_page')
                ->defaultValue(true)
            ->end()
            ->scalarNode('wrap_class')
                ->cannotBeEmpty()
                ->defaultValue('block-wrap')
            ->end()
            ->arrayNode('placeholders_items')
                ->useAttributeAsKey('name')
                ->prototype('array')
                ->children()
                    ->append($this->getPlaceholderItemsConfigTree())
                ->end()
            ->end()
        ->end();

        SettingsBuilder::append(
            $rootNode,
            array(
                'application_name' => array(
                    'value' => 'ORO',
                    'type' => 'scalar'
                ),
                'application_title' => array(
                    'value' => 'ORO Business Application Platform',
                    'type' => 'scalar'
                ),
            )
        );

        return $treeBuilder;
    }

    /**
     * Builds the configuration tree for placeholder items
     *
     * @return NodeDefinition
     */
    protected function getPlaceholderItemsConfigTree()
    {
        $builder = new TreeBuilder();
        $node    = $builder->root('items');

        $node
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
                ->booleanNode('remove')
                    ->validate()
                        // keep the 'remove' attribute only if its value is TRUE
                        ->ifTrue(
                            function ($v) {
                                return isset($v) && !$v;
                            }
                        )
                        ->thenUnset()
                    ->end()
                ->end()
                ->integerNode('order')->defaultValue(0)->end()
                ->scalarNode('action')->end()
                ->scalarNode('template')->end()
                ->arrayNode('attribute_instance_of')
                    ->prototype('scalar')->cannotBeEmpty()->end()
                    ->validate()
                        // the array must contain exactly 2 items
                        // the first item is an attribute name
                        // the second item is the class name
                        ->ifTrue(
                            function ($v) {
                                return 2 !== count($v);
                            }
                        )
                        ->thenInvalid(
                            'The "attribute_instance_of" attribute must contain exactly 2 items. %s'
                        )
                    ->end()
                ->end()
            ->end()
            ->validate()
                // remove all items with remove=TRUE or if neither 'action' nor 'template' attribute is not specified
                ->ifTrue(
                    function ($v) {
                        return (isset($v['remove']) && $v['remove'])
                            || (empty($v['action']) && empty($v['template']));
                    }
                )
                ->thenUnset()
            ->end()
            ->validate()
                // both 'action' and 'template' attributes should not be specified
                ->ifTrue(
                    function ($v) {
                        return !empty($v['action']) && !empty($v['template']);
                    }
                )
                ->thenInvalid('Only one either "action" or "template" attribute can be defined. %s')
            ->end()
            ->validate()
                // remove empty 'attribute_instance_of' attributes
                ->always(
                    function ($v) {
                        if (empty($v['attribute_instance_of'])) {
                            unset($v['attribute_instance_of']);
                        };
                        return $v;
                    }
                )
            ->end();

        // sort items by 'order' attribute
        $node
            ->validate()
                ->always(
                    function ($v) {
                        return $this->sortPlaceholderItems($v);
                    }
                )
            ->end();

        return $node;
    }

    /**
     * Sorts the given items by 'order' attribute
     *
     * @param array $items
     * @return mixed
     */
    protected function sortPlaceholderItems($items)
    {
        uasort(
            $items,
            function ($a, $b) {
                if ($a['order'] === $b['order']) {
                    return 0;
                }

                return ($a['order'] < $b['order']) ? -1 : 1;
            }
        );

        return $items;
    }
}
