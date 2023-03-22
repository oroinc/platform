<?php

namespace Oro\Bundle\SSOBundle\DependencyInjection;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSSOExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        if ($container instanceof ExtendedContainerBuilder) {
            $this->configureSecurityFirewalls($container);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $serviceLoader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $serviceLoader->load('services.yml');
    }

    private function configureSecurityFirewalls(ExtendedContainerBuilder $container): void
    {
        if (!$container->hasParameter('web_backend_prefix')) {
            return;
        }

        $backendPrefix = $container->getParameter('web_backend_prefix');
        if (!$backendPrefix) {
            return;
        }

        $updatedConfigs = [];
        $configs = $container->getExtensionConfig('security');
        foreach ($configs as $config) {
            if (isset($config['firewalls']['main']['oauth']['resource_owners'])) {
                $oauthResourceOwners = $config['firewalls']['main']['oauth']['resource_owners'];
                foreach ($oauthResourceOwners as $name => $path) {
                    $oauthResourceOwners[$name] = $backendPrefix . $path;
                }
                $config['firewalls']['main']['oauth']['resource_owners'] = $oauthResourceOwners;
            }
            $updatedConfigs[] = $config;
        }
        $container->setExtensionConfig('security', $updatedConfigs);
    }
}
