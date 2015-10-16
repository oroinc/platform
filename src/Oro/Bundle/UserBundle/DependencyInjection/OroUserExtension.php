<?php

namespace Oro\Bundle\UserBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroUserExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form.yml');
        $loader->load('importexport.yml');

        $container->setParameter('oro_user.reset.ttl', $config['reset']['ttl']);
        $container->setParameter('oro_user.privileges', $config['privileges']);
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $securityConfig = $container->getExtensionConfig('security');
        if (!isset($securityConfig[0]['firewalls']['main'])) {
            return;
        }

        // main firewall is the most general firewall, so it should be the last in list
        $mainFirewall = $securityConfig[0]['firewalls']['main'];
        unset($securityConfig[0]['firewalls']['main']);
        $securityConfig[0]['firewalls']['main'] = $mainFirewall;

        /** @var ExtendedContainerBuilder $container */
        $container->setExtensionConfig('security', $securityConfig);
    }
}
