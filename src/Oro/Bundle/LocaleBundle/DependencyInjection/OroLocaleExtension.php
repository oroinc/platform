<?php

namespace Oro\Bundle\LocaleBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;


use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroLocaleExtension extends Extension
{
    const PARAMETER_NAME_FORMATS = 'oro_locale.format.name';
    const PARAMETER_ADDRESS_FORMATS = 'oro_locale.format.address';
    const PARAMETER_LOCALE_DATA = 'oro_locale.locale_data';
    const PARAMETER_CURRENCY_DATA = 'oro_locale.currency_data';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configs = $this->processNameAndAddressFormatConfiguration($configs, $container);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->prepareSettings($config, $container);
        $container->setParameter(
            self::PARAMETER_NAME_FORMATS,
            $this->escapePercentSymbols($config['name_format'])
        );
        $container->setParameter(
            self::PARAMETER_ADDRESS_FORMATS,
            $this->escapePercentSymbols($config['address_format'])
        );
        $container->setParameter(
            self::PARAMETER_LOCALE_DATA,
            $this->escapePercentSymbols($config['locale_data'])
        );
        $container->setParameter(
            self::PARAMETER_CURRENCY_DATA,
            $this->escapePercentSymbols($config['currency_data'])
        );

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_types.yml');

        $this->addClassesToCompile(['Oro\Bundle\LocaleBundle\EventListener\LocaleListener']);
    }

    /**
     * Prepare locale system settings default values.
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function prepareSettings(array $config, ContainerBuilder $container)
    {
        $locale = LocaleSettings::getValidLocale(
            $this->getFinalizedParameter($config['settings']['locale']['value'], $container)
        );
        $config['settings']['locale']['value'] = $locale;
        if (empty($config['settings']['language']['value'])) {
            $config['settings']['language']['value'] = $locale;
        }
        if (empty($config['settings']['country']['value'])) {
            $config['settings']['country']['value'] = LocaleSettings::getCountryByLocale($locale);
        }
        $country = $config['settings']['country']['value'];
        if (empty($config['settings']['currency']['value'])
            && isset($config['locale_data'][$country]['currency_code'])
        ) {
            $config['settings']['currency']['value'] = $config['locale_data'][$country]['currency_code'];
        }
        $container->prependExtensionConfig('oro_locale', $config);
    }

    /**
     * @param string $parameter
     * @param ContainerBuilder $container
     * @return mixed
     */
    protected function getFinalizedParameter($parameter, ContainerBuilder $container)
    {
        if (is_string($parameter) && strpos($parameter, '%') === 0) {
            return $container->getParameter(str_replace('%', '', $parameter));
        }
        return $parameter;
    }

    /**
     * @param array|string $data
     * @return array|string
     */
    protected function escapePercentSymbols($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->escapePercentSymbols($value);
            }
        } elseif (is_string($data)) {
            $data = str_replace('%', '%%', $data);
        }

        return $data;
    }

    /**
     * @param ContainerBuilder $container
     * @return array
     */
    protected function parseExternalConfigFiles(ContainerBuilder $container)
    {
        $result = [
            'name_format'    => [],
            'address_format' => [],
            'locale_data'    => [],
            'currency_data'  => [],
        ];

        $configLoader = new CumulativeConfigLoader(
            'oro_locale',
            [
                new YamlCumulativeFileLoader('Resources/config/oro/name_format.yml'),
                new YamlCumulativeFileLoader('Resources/config/oro/address_format.yml'),
                new YamlCumulativeFileLoader('Resources/config/oro/locale_data.yml'),
                new YamlCumulativeFileLoader('Resources/config/oro/currency_data.yml')
            ]
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $result[$resource->name] = array_merge($result[$resource->name], $resource->data);
        }

        return $result;
    }

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @return array
     */
    protected function processNameAndAddressFormatConfiguration(array $configs, ContainerBuilder $container)
    {
        $externalData = $this->parseExternalConfigFiles($container);

        if (!empty($configs)) {
            $configData = array_shift($configs);
        } else {
            $configData = array();
        }

        // merge formats
        foreach (array('name_format', 'address_format', 'locale_data', 'currency_data') as $configKey) {
            if (!empty($configData[$configKey])) {
                $configData[$configKey] = array_merge($externalData[$configKey], $configData[$configKey]);
            } else {
                $configData[$configKey] = $externalData[$configKey];
            }
        }

        array_unshift($configs, $configData);

        return $configs;
    }
}
