<?php

namespace Oro\Bundle\LocaleBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NAME = 'oro_locale';

    public const DEFAULT_LOCALE   = 'en';
    public const DEFAULT_LANGUAGE = 'en';
    public const DEFAULT_COUNTRY  = 'US';

    public const LANGUAGE              = 'language';
    public const ENABLED_LOCALIZATIONS = 'enabled_localizations';
    public const DEFAULT_LOCALIZATION  = 'default_localization';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NAME);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode('formatting_code')->defaultValue(self::DEFAULT_LOCALE)->end()
            ->scalarNode('language')->defaultValue(self::DEFAULT_LANGUAGE)->end()
        ->end();

        /**
         * default values for "country" and "currency" are calculated automatically
         * @see \Oro\Bundle\LocaleBundle\DependencyInjection\OroLocaleExtension::prepareSettings
         */
        SettingsBuilder::append(
            $rootNode,
            [
                'country' => ['value' => null],
                'currency' => ['value' => null],
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

    public static function getConfigKeyByName(string $name): string
    {
        return TreeUtils::getConfigKey(self::ROOT_NAME, $name, ConfigManager::SECTION_MODEL_SEPARATOR);
    }

    public static function getFieldKeyByName(string $name): string
    {
        return TreeUtils::getConfigKey(self::ROOT_NAME, $name, ConfigManager::SECTION_VIEW_SEPARATOR);
    }
}
