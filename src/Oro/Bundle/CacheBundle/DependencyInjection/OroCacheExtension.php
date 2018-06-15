<?php

namespace Oro\Bundle\CacheBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroCacheExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('commands.yml');

        if ($container->getParameter('kernel.debug')) {
            $loader->load('debug.yml');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $validationConfig = [
            'validation' => [
                'cache' => 'oro_cache.validation_cache.doctrine',
            ],
            'serializer' => [
                'cache' => 'oro_cache.serializer',
            ],
            'annotations' => [
                'cache' => 'oro_cache.annotations',
            ],
        ];

        $container->prependExtensionConfig('framework', $validationConfig);
        $container->prependExtensionConfig(
            'jms_serializer',
            ['metadata' => ['cache' => 'oro_cache.jms_serializer_adapter']]
        );
    }
}
