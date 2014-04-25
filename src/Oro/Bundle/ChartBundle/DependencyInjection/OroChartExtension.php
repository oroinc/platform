<?php

namespace Oro\Bundle\ChartBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroChartExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $chartConfigs = array();
        $mergedConfig = array();

        $configLoader = new CumulativeConfigLoader(
            'oro_chart',
            new YamlCumulativeFileLoader('Resources/config/oro/chart.yml')
        );

        $resources = $configLoader->load($container);
        foreach ($resources as $resource) {
            $mergedConfig = array_replace_recursive($mergedConfig, $resource->data['oro_chart']);
        }

        foreach ($configs as $config) {
            $mergedConfig = array_replace_recursive($mergedConfig, $config);
        }

        $chartConfigs[] = $mergedConfig;

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $chartConfigs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->getDefinition('oro_chart.config_provider')->replaceArgument(0, $config);
    }
}
