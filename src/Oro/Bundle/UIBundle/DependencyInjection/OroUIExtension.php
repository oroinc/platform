<?php
declare(strict_types=1);

namespace Oro\Bundle\UIBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroUIExtension extends Extension
{
    public const ALIAS = 'oro_ui';

    /**
     * @throws \Exception If something went wrong
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('content_providers.yml');
        $loader->load('layouts.yml');
        $loader->load('block_types.yml');
        $loader->load('form_types.yml');
        $loader->load('controllers.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    public function getAlias()
    {
        return static::ALIAS;
    }
}
