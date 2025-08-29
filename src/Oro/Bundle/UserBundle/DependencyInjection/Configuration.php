<?php

namespace Oro\Bundle\UserBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('oro_user');
        $rootNode = $builder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('reset')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        // reset password token ttl, sec
                        ->scalarNode('ttl')
                            ->defaultValue(86400)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('privileges')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('label')->end()
                            ->scalarNode('view_type')->end()
                            ->arrayNode('types')->prototype('scalar')->end()->end()
                            ->scalarNode('field_type')->end()
                            ->booleanNode('fix_values')->end()
                            ->scalarNode('default_value')->end()
                            ->booleanNode('show_default')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('login_sources')
                    ->validate()
                        ->always(function (array $value) {
                            foreach ($value as $name => $config) {
                                foreach ($value as $innerName => $innerConfig) {
                                    if ($name === $innerName) {
                                        continue;
                                    }
                                    if ($config['code'] === $innerConfig['code']) {
                                        throw new \LogicException(sprintf(
                                            'The "code" option for "%s" and "%s" login sources are duplicated.',
                                            $name,
                                            $innerName
                                        ));
                                    }
                                }
                            }

                            return $value;
                        })
                    ->end()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('label')->end()
                            ->integerNode('code')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'password_min_length' => ['value' => 8, 'type' => 'scalar'],
                'password_lower_case' => ['value' => true, 'type' => 'boolean'],
                'password_upper_case' => ['value' => true, 'type' => 'boolean'],
                'password_numbers' => ['value' => true, 'type' => 'boolean'],
                'password_special_chars' => ['value' => false, 'type' => 'boolean'],
                'send_password_in_invitation_email' => ['type' => 'boolean', 'value' => false],
                'case_insensitive_email_addresses_enabled' => ['type' => 'boolean', 'value' => false],
                'user_login_password' => ['type' => 'boolean', 'value' => true],
            ]
        );

        return $builder;
    }
}
