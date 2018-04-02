<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class SettingsBuilder
{
    const RESOLVED_KEY = 'resolved';

    /**
     * @internal
     */
    const ALLOWED_TYPES = ['scalar', 'boolean', 'array'];

    /**
     *
     * @param ArrayNodeDefinition $root     Config root node
     * @param array               $settings
     */
    public static function append(ArrayNodeDefinition $root, $settings)
    {
        $builder = new TreeBuilder();
        $node    = $builder
            ->root('settings')
            ->addDefaultsIfNotSet()
            ->children()
            // additional flag to ensure that values are processed by "configuration processor"
            ->scalarNode(self::RESOLVED_KEY)->defaultTrue()->end();

        foreach ($settings as $name => $setting) {
            $child = $node
                ->arrayNode($name)
                ->addDefaultsIfNotSet()
                ->children();

            if (isset($setting['type']) && in_array($setting['type'], static::ALLOWED_TYPES)) {
                $type = $setting['type'];
            } else {
                $type = 'scalar';
            }

            switch ($type) {
                case 'scalar':
                    $child->scalarNode('value')->defaultValue($setting['value']);

                    break;
                case 'boolean':
                    $child->booleanNode('value')->defaultValue((bool)$setting['value']);

                    break;
                case 'array':
                    $child->arrayNode('value')
                        ->treatNullLike(array())
                        ->prototype('scalar')->end()
                        ->defaultValue(isset($setting['value'])? $setting['value'] : array());

                    break;
            }

            $child->scalarNode('scope')->defaultValue(isset($setting['scope']) ? $setting['scope'] : 'app');
        }

        $root->children()->append($node->end());
    }
}
