<?php

namespace Oro\Bundle\ChartBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroChartExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $chartConfigs = array();

        $configLoader = new CumulativeConfigLoader(
            'oro_chart',
            new YamlCumulativeFileLoader('Resources/config/oro/chart.yml')
        );

        $resources = $configLoader->load($container);
        foreach ($resources as $resource) {
            $chartConfigs[] = $resource->data['oro_chart'];
        }

        foreach ($configs as $config) {
            $chartConfigs[] = $config;
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $chartConfigs);

        $container->getDefinition('oro_chart.config_provider')->replaceArgument(0, $config);
    }
}
