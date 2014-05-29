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
            ->arrayNode('placeholders_items')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->append($this->getPlaceholderItemsConfigTree())
                        ->append($this->getPlaceholderBlocksConfigTree())
                    ->end()
                    ->validate()
                        // remove empty 'blocks' attribute and items within undefined blocks
                        ->always(
                            function ($v) {
                                if (empty($v['blocks'])) {
                                    unset($v['blocks']);
                                };
                                if (!empty($v['items'])) {
                                    $blocks           = isset($v['blocks'])
                                        ? array_map(
                                            function ($item) {
                                                return $item['name'];
                                            },
                                            $v['blocks']
                                        )
                                        : array();
                                    $items = array();
                                    $keys   = array_keys($v['items']);
                                    foreach ($keys as $key) {
                                        if (isset($v['items'][$key]['block'])) {
                                            $blockName = $v['items'][$key]['block'];
                                            if (in_array($blockName, $blocks)) {
                                                $items[] = $v['items'][$key];
                                            }
                                        } else {
                                            $items[] = $v['items'][$key];
                                        }
                                    }
                                    $v['items'] = $items;
                                }
                                return $v;
                            }
                        )
                    ->end()
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
                ->append($this->getRemoveAttributeConfigTree())
                ->integerNode('order')->defaultValue(0)->end()
                ->append($this->getInstanceOfAttributeConfigTree())
                ->scalarNode('block')->end()
                ->scalarNode('action')->end()
                ->scalarNode('template')->end()
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
                // remove empty 'attribute_instance_of' attribute
                ->always(
                    function ($v) {
                        if (empty($v['attribute_instance_of'])) {
                            unset($v['attribute_instance_of']);
                        };
                        return $v;
                    }
                )
            ->end();

        $this->addItemsSorting($node);

        return $node;
    }

    /**
     * Builds the configuration tree for placeholder blocks
     *
     * @return NodeDefinition
     */
    protected function getPlaceholderBlocksConfigTree()
    {
        $builder = new TreeBuilder();
        $node    = $builder->root('blocks');

        $node
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
                ->append($this->getRemoveAttributeConfigTree())
                ->integerNode('order')->defaultValue(0)->end()
                ->append($this->getInstanceOfAttributeConfigTree())
                ->scalarNode('label')->end()
            ->end()
            ->validate()
                // remove all items with remove=TRUE
                ->ifTrue(
                    function ($v) {
                        return (isset($v['remove']) && $v['remove']);
                    }
                )
                ->thenUnset()
            ->end()
            ->validate()
                // remove empty 'attribute_instance_of' attribute
                ->always(
                    function ($v) {
                        if (empty($v['attribute_instance_of'])) {
                            unset($v['attribute_instance_of']);
                        };
                        return $v;
                    }
                )
            ->end();

        $this->addItemsSorting($node);

        return $node;
    }

    /**
     * Builds the configuration tree for 'remove' attribute
     *
     * @return NodeDefinition
     */
    protected function getRemoveAttributeConfigTree()
    {
        $builder = new TreeBuilder();
        $node    = $builder->root('remove', 'boolean');

        $node
            ->validate()
            // keep the 'remove' attribute only if its value is TRUE
            ->ifTrue(
                function ($v) {
                    return isset($v) && !$v;
                }
            )
            ->thenUnset()
            ->end();

        return $node;
    }

    /**
     * Builds the configuration tree for 'attribute_instance_of' attribute
     *
     * @return NodeDefinition
     */
    protected function getInstanceOfAttributeConfigTree()
    {
        $builder = new TreeBuilder();
        $node    = $builder->root('attribute_instance_of');

        $node
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
            ->end();

        return $node;
    }

    /**
     * Add rules to sort items by 'order' attribute
     *
     * @param NodeDefinition $node
     */
    protected function addItemsSorting(NodeDefinition $node)
    {
        $node
            ->validate()
                ->always(
                    function ($v) {
                        return $this->sortItems($v);
                    }
                )
            ->end();
    }

    /**
     * Sorts the given items by 'order' attribute
     *
     * @param array $items
     * @return mixed
     */
    protected function sortItems($items)
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

        $result = array();
        foreach ($items as $name => $item) {
            $item['name'] = $name;
            $result[] = $item;
        }

        return $result;
    }
}
