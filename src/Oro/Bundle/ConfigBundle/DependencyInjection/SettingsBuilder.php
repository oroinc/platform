<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Helps to build manageable system configuration ("settings" section of a bundle configuration).
 */
class SettingsBuilder
{
    public const RESOLVED_KEY = 'resolved';

    private const ALLOWED_TYPES = ['scalar', 'boolean', 'array'];

    public static function append(ArrayNodeDefinition $root, array $settings): void
    {
        $builder = new TreeBuilder('settings');
        $node = $builder
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
            // additional flag to ensure that values are processed by "configuration processor"
            ->scalarNode(self::RESOLVED_KEY)->defaultTrue()->end();

        foreach ($settings as $name => $setting) {
            $child = $node
                ->arrayNode($name)
                ->addDefaultsIfNotSet()
                ->children();

            if (!isset($setting['type'])) {
                $type = 'scalar';
                if (isset($setting['value']) && \is_array($setting['value'])) {
                    $type = 'array';
                }
            } elseif (\in_array($setting['type'], self::ALLOWED_TYPES, true)) {
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
                        ->treatNullLike([])
                        ->prototype('variable')->end()
                        ->defaultValue($setting['value'] ?? []);
                    break;
            }

            $child->scalarNode('scope')->defaultValue($setting['scope'] ?? 'app');
        }

        $root->children()->append($node->end());
    }
}
