<?php

namespace Oro\Bundle\GoogleIntegrationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroGoogleIntegrationExtension extends Extension
{
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

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'oro_google_integration';
    }
}
