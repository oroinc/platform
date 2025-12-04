<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSecurityExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->prependExtensionConfig($this->getAlias(), SettingsBuilder::getSettings($config));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('layouts.yml');
        $loader->load('ownership.yml');
        $loader->load('services.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }

        if ($container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            $loader->load('services_debug.yml');
        }

        $this->configureCookieTokenStorage($container, $config);
        $this->configureStatelessCsrfProtection($container, $config, $loader);

        $container->setParameter('oro_security.login_target_path_excludes', $config['login_target_path_excludes']);
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        $container->setParameter('session_handler', 'oro.session_handler');
    }

    private function configureCookieTokenStorage(ContainerBuilder $container, array $config): void
    {
        $container->getDefinition('oro_security.csrf.cookie_token_storage')
            ->replaceArgument(0, $config['csrf_cookie']['cookie_secure'])
            ->replaceArgument(2, $config['csrf_cookie']['cookie_samesite']);
    }

    private function configureStatelessCsrfProtection(
        ContainerBuilder $container,
        array $config,
        Loader\YamlFileLoader $loader
    ): void {
        $csrfConfig = $config['stateless_csrf_protection'];
        if (!$csrfConfig['enabled']) {
            return;
        }

        $loader->load('stateless_csrf.yml');

        if (!$csrfConfig['stateless_token_ids']) {
            $container->removeDefinition('oro_security.csrf.same_origin_token_manager');

            return;
        }

        $container->getDefinition('oro_security.csrf.same_origin_token_manager')
            ->replaceArgument(3, $csrfConfig['stateless_token_ids'])
            ->replaceArgument(4, $csrfConfig['check_header'])
            ->replaceArgument(5, $csrfConfig['cookie_name']);
    }
}
