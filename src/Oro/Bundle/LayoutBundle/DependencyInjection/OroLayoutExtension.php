<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroLayoutExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('block_types.yml');

        if ($config['twig']['enabled']) {
            $loader->load('twig_renderer.yml');
            $container->setParameter('oro_layout.twig.resources', $config['twig']['resources']);
        } else {
            // @todo: PHP rendering is not implemented yet
            $loader->load('php_renderer.yml');
            $container->setParameter('oro_layout.php.resources', []);
        }
    }
}
