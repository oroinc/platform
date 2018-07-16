<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener;
use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallExceptionListener;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures Data API security firewalls to be able to work in two modes, stateless and statefull.
 * The statefull mode is used when API is called internally from web pages as AJAX request.
 */
class SecurityFirewallCompilerPass implements CompilerPassInterface
{
    /** @var array */
    private $contextListeners = [];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
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
     *
     * @param array $firewallConfig
     *
     * @return bool
     */
    private function isStatelessFirewallWithContext(array $firewallConfig): bool
    {
        return
            array_key_exists('stateless', $firewallConfig)
            && array_key_exists('context', $firewallConfig)
            && $firewallConfig['stateless']
            && $firewallConfig['context'];
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $firewallName
     * @param array            $firewallConfig
     */
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
        $sessionName = $this->getSessionName($container);

        // add the context listener
        $listenerId = $this->createContextListener($container, $contextKey);
        $apiContextListenerId = $listenerId . '.' . $firewallName;
        $container
            ->register($apiContextListenerId, SecurityFirewallContextListener::class)
            ->setArguments([new Reference($listenerId), $sessionName, new Reference('security.token_storage')]);
        $contextListeners = [];
        /** @var IteratorArgument $listeners */
        $listeners = $contextDef->getArgument(0);
        foreach ($listeners->getValues() as $listener) {
            // the context listener should be before the access listener
            if ('security.access_listener' === (string)$listener) {
                $contextListeners[] = new Reference($apiContextListenerId);
            }
            $contextListeners[] = $listener;
        }

        $contextDef->replaceArgument(0, $contextListeners);

        // replace the exception listener class
        $exceptionListenerDef = $container->getDefinition($contextDef->getArgument(1));
        $exceptionListenerDef->setClass(SecurityFirewallExceptionListener::class);
        $exceptionListenerDef->addMethodCall('setSessionName', [$sessionName]);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $contextKey
     *
     * @return string
     */
    private function createContextListener(ContainerBuilder $container, $contextKey): string
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

    /**
     * @param ContainerBuilder $container
     *
     * @return string
     */
    private function getSessionName(ContainerBuilder $container): string
    {
        $sessionOptions = $container->getParameter('session.storage.options');

        return $sessionOptions['name'];
    }
}
