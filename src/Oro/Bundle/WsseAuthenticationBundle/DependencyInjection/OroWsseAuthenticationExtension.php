<?php

namespace Oro\Bundle\WsseAuthenticationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Container extension for OroWsseAuthenticationBundle:
 *  - loads services
 */
class OroWsseAuthenticationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('commands.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'oro_wsse_authentication';
    }
}
