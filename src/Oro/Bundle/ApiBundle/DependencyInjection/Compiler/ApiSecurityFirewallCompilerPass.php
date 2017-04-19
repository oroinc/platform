<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener;
use Oro\Bundle\ApiBundle\Http\Firewall\ApiExceptionListener;

class ApiSecurityFirewallCompilerPass implements CompilerPassInterface
{
    /** @var array */
    private $contextListeners = [];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // config does not contains firewalls config
        $securityConfigs = $container->getExtensionConfig('security');
        if (!array_key_exists('firewalls', $securityConfigs[0])) {
            return;
        }

        // firewalls config is empty
        $firewalls = $securityConfigs[0]['firewalls'];
        if (empty($firewalls)) {
            return;
        }

        $sessionOptions = $container->getParameter('session.storage.options');
        foreach ($firewalls as $firewallName => $firewallConfig) {
            // process firewall only if it is stateless and have context parameter
            if (!array_key_exists('stateless', $firewallConfig)
                || !array_key_exists('context', $firewallConfig)
                || !$firewallConfig['stateless']
                || null === $firewallConfig['context']
            ) {
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
            $exceptionDefinition->setClass(ApiExceptionListener::class);
            $exceptionDefinition->addMethodCall('setSessionOptions', [$sessionOptions]);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $contextKey
     *
     * @return string
     */
    protected function createContextListener(ContainerBuilder $container, $contextKey)
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
