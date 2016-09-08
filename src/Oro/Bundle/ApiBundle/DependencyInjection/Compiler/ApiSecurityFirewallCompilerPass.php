<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener;
use Oro\Bundle\ApiBundle\Http\Firewall\ApiExceptionListener;

class ApiSecurityFirewallCompilerPass implements CompilerPassInterface
{
    /** @var string */
    protected $firewallName;

    /**
     * @param string $firewallName
     */
    public function __construct($firewallName)
    {
        $this->firewallName = $firewallName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $sessionOptions = $container->getParameter('session.storage.options');

        $contextId = 'security.firewall.map.context.' . $this->firewallName;
        if (!$container->hasDefinition($contextId)) {
            return;
        }

        // replace the context listener
        $contextDef = $container->getDefinition($contextId);
        $listeners = $contextDef->getArgument(0);
        list($listenerIndex, $listenerRef) = $this->getContextListener($container, $listeners);
        if (null === $listenerIndex) {
            return;
        }

        $apiListenerId = (string)$listenerRef . '.' . $this->firewallName;
        $container
            ->register($apiListenerId, SecurityFirewallContextListener::class)
            ->setArguments([$listenerRef, $sessionOptions]);

        $listeners[$listenerIndex] = new Reference($apiListenerId);
        $contextDef->replaceArgument(0, $listeners);

        // replace the exception listener class
        $exceptionListenerRef = $contextDef->getArgument(1);
        $exceptionDefinition = $container->getDefinition($exceptionListenerRef);
        $exceptionDefinition->setClass(ApiExceptionListener::class);
        $exceptionDefinition->addMethodCall('setSessionOptions', [$sessionOptions]);
    }

    /**
     * @param ContainerBuilder $container
     * @param Reference[]      $listeners
     *
     * @return array [index, Reference, Definition]
     */
    protected function getContextListener(ContainerBuilder $container, array $listeners)
    {
        $index = 0;
        foreach ($listeners as $listener) {
            $serviceId = (string)$listener;
            if ($container->hasDefinition($serviceId)) {
                $serviceDef = $container->getDefinition($serviceId);
                if ('Symfony\Component\Security\Http\Firewall\ContextListener' === $serviceDef->getClass()) {
                    return [$index, $listener];
                }
            }
            $index++;
        }

        return [null, null];
    }
}
