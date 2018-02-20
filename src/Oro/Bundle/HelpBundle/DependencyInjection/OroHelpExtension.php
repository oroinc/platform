<?php

namespace Oro\Bundle\HelpBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroHelpExtension extends Extension
{
    const HELP_FILE_CONFIG_ROOT = 'help';
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->setConfigurationToLinkProvider($configs, $container);
    }

    /**
     * Reads configuration from all bundles and from the application and injects to oro_help.model.help_link_provider
     *
     * @param array $configs
     * @param ContainerBuilder $container
     * @return array
     */
    protected function setConfigurationToLinkProvider(array $configs, ContainerBuilder $container)
    {
        $applicationConfig = $this->processConfiguration(new ApplicationConfiguration(), $configs);
        $bundleConfig = $this->processConfiguration(new BundleConfiguration(), $this->getBundleConfigs($container));

        $configuration = array_merge_recursive($bundleConfig, $applicationConfig);

        $linkProvider = $container->getDefinition('oro_help.model.help_link_provider');
        $linkProvider->addMethodCall('setConfiguration', array($configuration));
    }

    /**
     * Get a list of configs from all bundles
     *
     * @param ContainerBuilder $container
     * @return array
     */
    protected function getBundleConfigs(ContainerBuilder $container)
    {
        $result = [];

        $configLoader = new CumulativeConfigLoader(
            'oro_help',
            new YamlCumulativeFileLoader('Resources/config/oro/help.yml')
        );
        $resources = $configLoader->load($container);
        foreach ($resources as $resource) {
            if (array_key_exists(self::HELP_FILE_CONFIG_ROOT, $resource->data)) {
                $result[] = $resource->data[self::HELP_FILE_CONFIG_ROOT];
            }
        }

        return $result;
    }
}
