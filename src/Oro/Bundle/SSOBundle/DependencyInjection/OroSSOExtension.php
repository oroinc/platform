<?php

namespace Oro\Bundle\SSOBundle\DependencyInjection;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSSOExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if (!$container->hasParameter('web_backend_prefix') || empty($container->getParameter('web_backend_prefix'))) {
            return;
        }

        if (!$container instanceof ExtendedContainerBuilder) {
            return;
        }

        $formattedConfigs = [];
        foreach ($container->getExtensionConfig('security') as $config) {
            if (isset($config['firewalls']['main']['oauth']['resource_owners'])) {
                $oauthResourceOwners = $config['firewalls']['main']['oauth']['resource_owners'];
                foreach ($oauthResourceOwners as $name => $path) {
                    $prefix = $container->getParameter('web_backend_prefix');
                    $oauthResourceOwners[$name] = $prefix . $path;
                }
                $config['firewalls']['main']['oauth']['resource_owners'] = $oauthResourceOwners;
            }
            $formattedConfigs[] = $config;
        }
        $container->setExtensionConfig('security', $formattedConfigs);
    }

    /**
     * Load
     *
     * @param  array            $configs
     * @param  ContainerBuilder $container
     * @throws InvalidConfigurationException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $serviceLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $serviceLoader->load('services.yml');

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'oro_sso';
    }
}
