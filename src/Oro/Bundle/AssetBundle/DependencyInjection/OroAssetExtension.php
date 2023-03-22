<?php

namespace Oro\Bundle\AssetBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroAssetExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('oro_asset.with_babel', $config['with_babel']);
        $container->setParameter('oro_asset.nodejs_path', $config['nodejs_path']);
        $container->setParameter('oro_asset.npm_path', $config['npm_path']);
        $container->setParameter('oro_asset.build_timeout', $config['build_timeout']);
        $container->setParameter('oro_asset.npm_install_timeout', $config['npm_install_timeout']);
        $container->setParameter('oro_asset.webpack_dev_server_options', $config['webpack_dev_server']);
    }
}
