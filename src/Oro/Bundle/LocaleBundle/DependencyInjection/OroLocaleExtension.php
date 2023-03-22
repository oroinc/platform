<?php

namespace Oro\Bundle\LocaleBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\LocaleBundle\Configuration\DefaultCurrencyValueProvider;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroLocaleExtension extends Extension
{
    public const PARAMETER_FORMATTING_CODE = 'oro_locale.formatting_code';
    public const PARAMETER_LANGUAGE = 'oro_locale.language';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $this->prepareSettings($config, $container);

        $container->setParameter(self::PARAMETER_FORMATTING_CODE, $config['formatting_code']);
        $container->setParameter(self::PARAMETER_LANGUAGE, $config['language']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('duplicator.yml');
        $loader->load('form_types.yml');
        $loader->load('importexport.yml');
        $loader->load('cache.yml');
        $loader->load('services_api.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
    }

    private function prepareSettings(array $config, ContainerBuilder $container): void
    {
        if (empty($config['settings']['country']['value'])) {
            $config['settings']['country']['value'] = LocaleSettings::getCountryByLocale($config['formatting_code']);
        }

        if (empty($config['settings']['currency']['value'])) {
            $country = $config['settings']['country']['value'];
            if ($country) {
                $providerServiceId = 'oro_locale.provider.default_value.currency';
                $container->register($providerServiceId, DefaultCurrencyValueProvider::class)
                    ->setPublic(false)
                    ->setArguments([$country, new Reference('oro_locale.locale_data_configuration.provider')]);
                $config['settings']['currency']['value'] = '@' . $providerServiceId;
            }
        }

        $container->prependExtensionConfig($this->getAlias(), SettingsBuilder::getSettings($config));
    }
}
