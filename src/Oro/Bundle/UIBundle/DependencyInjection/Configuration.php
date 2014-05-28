<?php

namespace Oro\Bundle\UIBundle\DependencyInjection;

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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                    ->arrayNode('items')
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                        ->children()
                            ->booleanNode('remove')
                                ->validate()
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
                            ->ifTrue(
                                function ($v) {
                                    return (isset($v['remove']) && $v['remove'])
                                        || (empty($v['action']) && empty($v['template']));
                                }
                            )
                            ->thenUnset()
                        ->end()
                        ->validate()
                            ->ifTrue(
                                function ($v) {
                                    return !empty($v['action']) && !empty($v['template']);
                                }
                            )
                            ->thenInvalid('Only one either "action" or "template" attribute can be defined. %s')
                        ->end()
                        ->validate()
                            ->always(
                                function ($v) {
                                    if (empty($v['attribute_instance_of'])) {
                                        unset($v['attribute_instance_of']);
                                    };
                                    return $v;
                                }
                            )
                        ->end()
                    ->end()
                    ->validate()
                        ->always(
                            function ($v) {
                                uasort(
                                    $v,
                                    function ($a, $b) {
                                        if ($a['order'] === $b['order']) {
                                            return 0;
                                        }

                                        return ($a['order'] < $b['order']) ? -1 : 1;
                                    }
                                );
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
}
