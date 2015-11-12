<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
        $loader->load('processors.build_config.yml');
        $loader->load('processors.get_list.yml');
        $loader->load('processors.get.yml');

        $configLoader = new CumulativeConfigLoader(
            'oro_entity',
            new YamlCumulativeFileLoader('Resources/config/oro/api.yml')
        );
        $resources    = $configLoader->load($container);
        $entityConfig = [];
        foreach ($resources as $resource) {
            $entityConfig = $this->mergeEntityConfiguration($resource, $entityConfig);
        }
        $configBagDef = $container->getDefinition('oro_api.config_bag');
        $configBagDef->replaceArgument(0, $entityConfig);
    }

    /**
     * @param CumulativeResourceInfo $resource
     * @param array                  $data
     *
     * @return array
     */
    protected function mergeEntityConfiguration(CumulativeResourceInfo $resource, array $data)
    {
        if (!empty($resource->data['oro_api']['entity'])) {
            $data = array_merge(
                $data,
                $resource->data['oro_api']['entity']
            );
        }

        return $data;
    }
}
