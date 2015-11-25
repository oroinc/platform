<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroApiExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('processors.normalize_value.yml');
        $loader->load('processors.get_config.yml');
        $loader->load('processors.get_metadata.yml');
        $loader->load('processors.get_list.yml');
        $loader->load('processors.get.yml');

        $this->loadApiConfiguration($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadApiConfiguration(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_api',
            new YamlCumulativeFileLoader('Resources/config/oro/api.yml')
        );
        $resources    = $configLoader->load($container);
        $apiConfig    = [];
        foreach ($resources as $resource) {
            $apiConfig = $this->mergeApiConfiguration($resource, $apiConfig);
        }
        $configBagDef = $container->getDefinition('oro_api.config_bag');
        $configBagDef->replaceArgument(0, $apiConfig);
    }

    /**
     * @param CumulativeResourceInfo $resource
     * @param array                  $data
     *
     * @return array
     */
    protected function mergeApiConfiguration(CumulativeResourceInfo $resource, array $data)
    {
        if (!empty($resource->data['oro_api'])) {
            $data = array_merge(
                $data,
                $resource->data['oro_api']
            );
        }

        return $data;
    }
}
