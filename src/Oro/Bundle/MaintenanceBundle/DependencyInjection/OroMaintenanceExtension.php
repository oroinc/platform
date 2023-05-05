<?php

namespace Oro\Bundle\MaintenanceBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroMaintenanceExtension extends Extension
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

        $this->configureMaintenanceParameters($container, $config);
    }

    private function configureMaintenanceParameters(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('oro_maintenance.driver', $config['driver']);
        $container->setParameter('oro_maintenance.authorized.path', $config['authorized']['path']);
        $container->setParameter('oro_maintenance.authorized.host', $config['authorized']['host']);
        $container->setParameter('oro_maintenance.authorized.ips', $config['authorized']['ips']);
        $container->setParameter('oro_maintenance.authorized.query', $config['authorized']['query']);
        $container->setParameter('oro_maintenance.authorized.cookie', $config['authorized']['cookie']);
        $container->setParameter('oro_maintenance.authorized.route', $config['authorized']['route']);
        $container->setParameter('oro_maintenance.authorized.attributes', $config['authorized']['attributes']);
        $container->setParameter('oro_maintenance.response.http_code', $config['response']['code']);
        $container->setParameter('oro_maintenance.response.http_status', $config['response']['status']);
        $container->setParameter(
            'oro_maintenance.response.exception_message',
            $config['response']['exception_message']
        );
    }
}
