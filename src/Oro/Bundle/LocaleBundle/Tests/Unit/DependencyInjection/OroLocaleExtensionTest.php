<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LocaleBundle\Configuration\DefaultCurrencyValueProvider;
use Oro\Bundle\LocaleBundle\DependencyInjection\OroLocaleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class OroLocaleExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroLocaleExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'country' => ['value' => 'US', 'scope' => 'app'],
                        'currency' => ['value' => '@oro_locale.provider.default_value.currency', 'scope' => 'app'],
                        'timezone' => ['value' => \date_default_timezone_get(), 'scope' => 'app'],
                        'format_address_by_address_country' => ['value' => true, 'scope' => 'app'],
                        'qwerty' => ['value' => [], 'scope' => 'app'],
                        'quarter_start' => ['value' => ['month' => '1', 'day' => '1'], 'scope' => 'app'],
                        'temperature_unit' => ['value' => 'fahrenheit', 'scope' => 'app'],
                        'wind_speed_unit' => ['value' => 'miles_per_hour', 'scope' => 'app'],
                        'enabled_localizations' => ['value' => [], 'scope' => 'app'],
                        'default_localization' => ['value' => null, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_locale')
        );

        self::assertEquals('en', $container->getParameter('oro_locale.formatting_code'));
        self::assertEquals('en', $container->getParameter('oro_locale.language'));

        $defaultCurrencyValueProviderDef = new Definition(
            DefaultCurrencyValueProvider::class,
            ['US', new Reference('oro_locale.locale_data_configuration.provider')]
        );
        $defaultCurrencyValueProviderDef->setPublic(false);
        self::assertEquals(
            $defaultCurrencyValueProviderDef,
            $container->getDefinition('oro_locale.provider.default_value.currency')
        );
    }
}
