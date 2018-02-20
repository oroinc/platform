<?php

namespace Oro\Bundle\SidebarBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSidebarExtension extends Extension
{
    const WIDGETS_SETTINGS_PARAMETER = 'oro_sidebar.sidebar_widgets_definitions';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        array_unshift(
            $configs,
            array('sidebar_widgets' => $this->getBundlesSettings($container))
        );

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(self::WIDGETS_SETTINGS_PARAMETER, $config['sidebar_widgets']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    /**
     * Gets bundles side bar configuration
     *
     * @param ContainerBuilder $container
     * @return array
     */
    protected function getBundlesSettings(ContainerBuilder $container)
    {
        $result = array();

        $configLoader = new CumulativeConfigLoader(
            'oro_sidebar',
            new FolderingCumulativeFileLoader(
                '{folder}',
                '\w+',
                new YamlCumulativeFileLoader('Resources/public/sidebar_widgets/{folder}/widget.yml')
            )
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $result[basename(dirname($resource->path))] = $resource->data;
        }

        return $result;
    }
}
