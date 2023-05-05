<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Security\FeatureDependedFirewallMap;
use Oro\Bundle\ApiBundle\Security\Http\Firewall\ContextListener;
use Oro\Bundle\ApiBundle\Security\Http\Firewall\ExceptionListener;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Changes the class for "security.firewall.map" service to be able to disable API firewall listeners.
 * Configures API security firewalls to be able to work in two modes, stateless and statefull.
 * The statefull mode is used when API is called internally from web pages as AJAX request.
 */
class SecurityFirewallCompilerPass implements CompilerPassInterface
{
    private array $contextListeners = [];

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        // configure the firewall map service to be able to disable listeners if API feature is disabled
        $config = DependencyInjectionUtil::getConfig($container);
        $container->getDefinition('security.firewall.map')
            ->setClass(FeatureDependedFirewallMap::class)
            ->addArgument(new Reference('oro_featuretoggle.checker.feature_checker'))
            ->addArgument(new Reference('oro_api.security.firewall.feature_access_listener'))
            ->addArgument($config['api_firewalls']);

        $securityConfigs = $container->getExtensionConfig('security');
        if (empty($securityConfigs[0]['firewalls'])) {
            return;
        }

        foreach ($securityConfigs[0]['firewalls'] as $name => $config) {
            if ($this->isStatelessFirewallWithContext($config)) {
                $this->configureStatelessFirewallWithContext($container, $name, $config);
            }
        }
    }

    /**
     * Checks whether a firewall is stateless and have context parameter
     */
    private function isStatelessFirewallWithContext(array $firewallConfig): bool
    {
        return
            \array_key_exists('stateless', $firewallConfig)
            && \array_key_exists('context', $firewallConfig)
            && $firewallConfig['stateless']
            && $firewallConfig['context'];
    }

    private function configureStatelessFirewallWithContext(
        ContainerBuilder $container,
        string $firewallName,
        array $firewallConfig
    ): void {
        $contextId = 'security.firewall.map.context.' . $firewallName;
        if (!$container->hasDefinition($contextId)) {
            return;
        }

        $contextDef = $container->getDefinition($contextId);
        $contextKey = $firewallConfig['context'];

        // add the context listener
        $listenerId = $this->createContextListener($container, $contextKey);
        $apiContextListenerId = $listenerId . '.' . $firewallName;
        $container
            ->register($apiContextListenerId, ContextListener::class)
            ->setArguments([
                new Reference($listenerId),
                new Reference('security.token_storage')
            ])
            ->addMethodCall('setCsrfRequestManager', [new Reference('oro_security.csrf_request_manager')])
            ->addMethodCall(
                'setCsrfProtectedRequestHelper',
                [new Reference('oro_security.csrf_protected_request_helper')]
            );
        $contextListeners = [];
        /** @var IteratorArgument $listeners */
        $listeners = $contextDef->getArgument(0);
        $wasSet = false;
        foreach ($listeners->getValues() as $listener) {
            $id = (string)$listener;
            // the context listener should be before the access listener or remember me listener
            if (false === $wasSet
                && (
                    'security.access_listener' === $id
                    || str_starts_with($id, 'oro_security.authentication.listener.rememberme')
                )
            ) {
                $wasSet = true;
                $contextListeners[] = new Reference($apiContextListenerId);
            }
            $contextListeners[] = $listener;
        }

        $contextDef->replaceArgument(0, new IteratorArgument($contextListeners));

        // replace the exception listener class
        $exceptionListenerDef = $container->getDefinition($contextDef->getArgument(1));
        $exceptionListenerDef->setClass(ExceptionListener::class);
    }

    private function createContextListener(ContainerBuilder $container, string $contextKey): string
    {
        if (isset($this->contextListeners[$contextKey])) {
            return $this->contextListeners[$contextKey];
        }

        $listenerId = 'oro_security.context_listener.' . $contextKey;
        $container
            ->setDefinition($listenerId, new ChildDefinition('security.context_listener'))
            ->replaceArgument(2, $contextKey);

        $this->contextListeners[$contextKey] = $listenerId;

        return $listenerId;
    }
}
