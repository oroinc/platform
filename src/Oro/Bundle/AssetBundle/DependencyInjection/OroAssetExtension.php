<?php

namespace Oro\Bundle\AssetBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 */
class OroAssetExtension extends Extension
{
    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('oro_asset.nodejs_path', $config['nodejs_path']);
        $container->setParameter('oro_asset.npm_path', $config['npm_path']);
        $container->setParameter('oro_asset.build_timeout', $config['build_timeout']);
        $container->setParameter('oro_asset.npm_install_timeout', $config['npm_install_timeout']);
    }
}
