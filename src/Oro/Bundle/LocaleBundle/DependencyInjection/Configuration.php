<?php

namespace Oro\Bundle\LocaleBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files
 */
class Configuration implements ConfigurationInterface
{
    const ROOT_NAME   = 'oro_locale';

    const DEFAULT_LOCALE   = 'en';
    const DEFAULT_LANGUAGE = 'en';
    const DEFAULT_COUNTRY  = 'US';

    const LANGUAGE = 'language';
    const ENABLED_LOCALIZATIONS = 'enabled_localizations';
    const DEFAULT_LOCALIZATION = 'default_localization';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder
            ->root(self::ROOT_NAME)
            ->children()
                ->scalarNode('formatting_code')->defaultValue(self::DEFAULT_LOCALE)->end()
                ->scalarNode('language')->defaultValue(self::DEFAULT_LANGUAGE)->end()
                ->arrayNode('name_format')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('address_format')
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
                    ->end()
                ->end()
                ->arrayNode('locale_data')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('default_locale')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('currency_code')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('phone_prefix')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('currency_data')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('symbol')
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        // null values set as default for country because
        // their values will be calculated by Extension based on kernel`s default locale
        SettingsBuilder::append(
            $rootNode,
            [
                'country' => ['value' => null],
                'timezone' => ['value' => date_default_timezone_get()],
                'format_address_by_address_country' => ['value' => true, 'type' => 'boolean'],
                'qwerty' => ['value' => [], 'type' => 'array'],
                'quarter_start' => ['value' => ['month' => '1', 'day' => '1']],
                'temperature_unit' => ['value' => 'fahrenheit'],
                'wind_speed_unit' => ['value' => 'miles_per_hour'],
                self::ENABLED_LOCALIZATIONS => ['value' => [], 'type' => 'array'],
                self::DEFAULT_LOCALIZATION => ['value' => null]
            ]
        );

        return $treeBuilder;
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getConfigKeyByName($name)
    {
        return sprintf('oro_locale%s%s', ConfigManager::SECTION_MODEL_SEPARATOR, $name);
    }
}
