<?php

namespace Oro\Bundle\SanitizeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * OroSanitizeBundle bundle extension.
 */
class OroSanitizeExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('commands.yml');

        $container->setParameter('oro_sanitize.entity_config_connection', $config['entity_config_connection']);
        $container->setParameter('oro_sanitize.custom_email_domain', $config['custom_email_domain']);
        $container->setParameter('oro_sanitize.generic_phone_mask', $config['generic_phone_mask']);

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }
    }
}
