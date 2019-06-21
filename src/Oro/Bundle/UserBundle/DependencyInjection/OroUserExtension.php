<?php

namespace Oro\Bundle\UserBundle\DependencyInjection;

use Oro\Bundle\SecurityBundle\DependencyInjection\Extension\SecurityExtensionHelper;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroUserExtension extends Extension implements PrependExtensionInterface
{
    const ALIAS = 'oro_user';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services_api.yml');
        $loader->load('form.yml');
        $loader->load('importexport.yml');
        $loader->load('mass_actions.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');

        $container->setParameter('oro_user.reset.ttl', $config['reset']['ttl']);
        $container->setParameter('oro_user.privileges', $config['privileges']);

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        /** @var ExtendedContainerBuilder $container */
        SecurityExtensionHelper::makeFirewallLatest($container, 'main');
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
