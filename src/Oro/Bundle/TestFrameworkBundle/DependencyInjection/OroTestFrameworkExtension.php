<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroTestFrameworkExtension extends Extension implements PrependExtensionInterface
{
    private const INSTALL_DEFAULT_OPTIONS_HOLDER_SERVICE = 'oro_test.provider.install_default_options';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('importexport_test.yml');
        $loader->load('form_types.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('services_test.yml');

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        if ($container->hasDefinition(self::INSTALL_DEFAULT_OPTIONS_HOLDER_SERVICE) && $config) {
            $definition = $container->getDefinition(self::INSTALL_DEFAULT_OPTIONS_HOLDER_SERVICE);
            $definition->replaceArgument(0, $config['install_options']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('profiler.enabled')) {
            $container->setParameter('profiler.enabled', false);
        }
    }
}
