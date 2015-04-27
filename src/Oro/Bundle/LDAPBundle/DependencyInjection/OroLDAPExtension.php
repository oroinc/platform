<?php

namespace Oro\Bundle\LDAPBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroLDAPExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $serviceLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $serviceLoader->load('services.yml');
        $serviceLoader->load('form.yml');

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'oro_ldap';
    }
}
