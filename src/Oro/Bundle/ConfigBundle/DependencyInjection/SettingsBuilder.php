<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Helps to build manageable system configuration ("settings" section of a bundle configuration).
 */
class SettingsBuilder
{
    private const SETTINGS_KEY = 'settings';
    private const RESOLVED_KEY = 'resolved';
    private const TYPE_KEY = 'type';
    private const VALUE_KEY = 'value';
    private const SCOPE_KEY = 'scope';
    private const SCALAR_TYPE = 'scalar';
    private const BOOLEAN_TYPE = 'boolean';
    private const ARRAY_TYPE = 'array';
    private const ALLOWED_TYPES = [self::SCALAR_TYPE, self::BOOLEAN_TYPE, self::ARRAY_TYPE];

    public static function getSettings(array $config): array
    {
        if (!\array_key_exists(self::SETTINGS_KEY, $config)) {
            throw new \LogicException(sprintf('The config must contains "%s" section.', self::SETTINGS_KEY));
        }

        return [self::SETTINGS_KEY => $config[self::SETTINGS_KEY]];
    }

    public static function append(ArrayNodeDefinition $root, array $settings): void
    {
        $builder = new TreeBuilder(self::SETTINGS_KEY);
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

            if (!isset($setting[self::TYPE_KEY])) {
                $type = self::SCALAR_TYPE;
                if (isset($setting[self::VALUE_KEY]) && \is_array($setting[self::VALUE_KEY])) {
                    $type = self::ARRAY_TYPE;
                }
            } elseif (\in_array($setting[self::TYPE_KEY], self::ALLOWED_TYPES, true)) {
                $type = $setting[self::TYPE_KEY];
            } else {
                $type = self::SCALAR_TYPE;
            }

            switch ($type) {
                case self::SCALAR_TYPE:
                    $child->scalarNode(self::VALUE_KEY)->defaultValue($setting[self::VALUE_KEY]);
                    break;
                case self::BOOLEAN_TYPE:
                    $child->booleanNode(self::VALUE_KEY)->defaultValue((bool)$setting[self::VALUE_KEY]);
                    break;
                case self::ARRAY_TYPE:
                    $child->arrayNode(self::VALUE_KEY)
                        ->treatNullLike([])
                        ->prototype('variable')->end()
                        ->defaultValue($setting[self::VALUE_KEY] ?? []);
                    break;
            }

            $child->scalarNode(self::SCOPE_KEY)->defaultValue($setting[self::SCOPE_KEY] ?? 'app');
        }

        $root->children()->append($node->end());
    }
}
