<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener;
use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallExceptionListener;

/**
 * Configures Data API security firewalls.
 */
class SecurityFirewallCompilerPass implements CompilerPassInterface
{
    /** @var array */
    private $contextListeners = [];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $securityConfigs = $container->getExtensionConfig('security');
        if (empty($securityConfigs[0]['firewalls'])) {
            return;
        }

        $sessionOptions = $container->getParameter('session.storage.options');
        $firewalls = $securityConfigs[0]['firewalls'];
        foreach ($firewalls as $firewallName => $firewallConfig) {
            if (!$this->isStatelessFirewallWithContext($firewallConfig)) {
                continue;
            }

            $contextId = 'security.firewall.map.context.' . $firewallName;
            if (!$container->hasDefinition($contextId)) {
                continue;
            }

            $contextDef = $container->getDefinition($contextId);

            // add the context listener
            $listeners = $contextDef->getArgument(0);
            $contextKey = $firewallConfig['context'];
            // get new context listener reference
            $listenerRef = new Reference($this->createContextListener($container, $contextKey));
            // create decorator for the Context serializer listener
            $apiContextSerializerListenerId = (string)$listenerRef . '.' . $firewallName;
            $container
                ->register($apiContextSerializerListenerId, SecurityFirewallContextListener::class)
                ->setArguments(
                    [
                        $listenerRef,
                        $sessionOptions,
                        new Reference('security.token_storage')
                    ]
                );
            $apiContextSerializerListener = new Reference($apiContextSerializerListenerId);
            $contextListeners = [];
            /** @var Reference $listener */
            foreach ($listeners as $listener) {
                // Context serializer listener should does before the access listener
                if ((string)$listener === 'security.access_listener') {
                    $contextListeners[] = $apiContextSerializerListener;
                }
                $contextListeners[] = $listener;
            }

            $contextDef->replaceArgument(0, $contextListeners);

            // replace the exception listener class
            $exceptionListenerRef = $contextDef->getArgument(1);
            $exceptionDefinition = $container->getDefinition($exceptionListenerRef);
            $exceptionDefinition->setClass(SecurityFirewallExceptionListener::class);
            $exceptionDefinition->addMethodCall('setSessionOptions', [$sessionOptions]);
        }
    }

    /**
     * Checks whether a firewall is stateless and have context parameter
     *
     * @param array $firewallConfig
     *
     * @return bool
     */
    private function isStatelessFirewallWithContext(array $firewallConfig)
    {
        return
            array_key_exists('stateless', $firewallConfig)
            && array_key_exists('context', $firewallConfig)
            && $firewallConfig['stateless']
            && $firewallConfig['context'];
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $contextKey
     *
     * @return string
     */
    private function createContextListener(ContainerBuilder $container, $contextKey)
    {
        if (isset($this->contextListeners[$contextKey])) {
            return $this->contextListeners[$contextKey];
        }

        $listenerId = 'oro_security.context_listener.' . $contextKey;
        $listener = $container->setDefinition($listenerId, new DefinitionDecorator('security.context_listener'));
        $listener->replaceArgument(2, $contextKey);

        $this->contextListeners[$contextKey] = $listenerId;

        return $listenerId;
    }
}
