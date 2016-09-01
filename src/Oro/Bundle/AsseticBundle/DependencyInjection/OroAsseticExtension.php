<?php

namespace Oro\Bundle\AsseticBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroAsseticExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter(
            'oro_assetic.raw_configuration',
            $this->getBundlesAssetsConfiguration($container, $config)
        );
    }

    /**
     * Get array with assets from config files
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @return array
     */
    public function getBundlesAssetsConfiguration(ContainerBuilder $container, array $config)
    {
        $result = array(
            'css_debug_groups' => $config['css_debug'],
            'css_debug_all'    => $config['css_debug_all'],
            'css'              => array()
        );

        $configLoader = new CumulativeConfigLoader(
            'oro_assetic',
            new YamlCumulativeFileLoader('Resources/config/assets.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            if (isset($resource->data['css'])) {
                $result['css'] = array_merge_recursive($result['css'], $resource->data['css']);
            }
        }

        return $result;
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'oro_assetic';
    }
}
