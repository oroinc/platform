<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroTestFrameworkExtension extends Extension implements PrependExtensionInterface
{
    private const INSTALL_DEFAULT_OPTIONS_HOLDER_SERVICE = 'oro_test.provider.install_default_options';

    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('importexport_test.yml');
        $loader->load('form_types.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('services_test.yml');

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        if ($container->hasDefinition(self::INSTALL_DEFAULT_OPTIONS_HOLDER_SERVICE) && $config) {
            $definition = $container->getDefinition(self::INSTALL_DEFAULT_OPTIONS_HOLDER_SERVICE);
            $definition->replaceArgument(0, $config['install_options']);
        }
    }

    #[\Override]
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('profiler.enabled')) {
            $container->setParameter('profiler.enabled', false);
        }

        if ($container instanceof ExtendedContainerBuilder) {
            $this->configureSecurityFirewalls($container);
        }
    }

    private function configureSecurityFirewalls(ExtendedContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());

        $firewalls = [];
        foreach ($configs as $config) {
            if (!empty($config['test_auth_firewalls']) && \is_array($config['test_auth_firewalls'])) {
                $firewalls[] = $config['test_auth_firewalls'];
            }
        }
        $firewalls = array_unique(array_merge(...$firewalls));

        $securityConfigs = $container->getExtensionConfig('security');

        $providers = $securityConfigs[0]['providers'];
        foreach ($firewalls as $firewallName) {
            if (!empty($securityConfigs[0]['firewalls'][$firewallName])) {
                $firewallConfig = $securityConfigs[0]['firewalls'][$firewallName];
                $serviceName = 'oro_test.security.core.test_authentication.' . $firewallName;

                $customAuthenticators = $securityConfigs[0]['firewalls'][$firewallName]['custom_authenticators'] ?? [];
                $customAuthenticators[] = $serviceName;
                $securityConfigs[0]['firewalls'][$firewallName]['custom_authenticators'] = $customAuthenticators;

                $authenticator = new ChildDefinition('oro_test.security.core.test_authentication');
                $authenticator->addMethodCall(
                    'setUserProvider',
                    [new Reference($providers[$firewallConfig['provider']]['id'])]
                );
                $authenticator->addMethodCall(
                    'setFirewallName',
                    [$firewallName]
                );
                $container->setDefinition($serviceName, $authenticator);
            }
        }
        $container->setExtensionConfig('security', $securityConfigs);
    }
}
