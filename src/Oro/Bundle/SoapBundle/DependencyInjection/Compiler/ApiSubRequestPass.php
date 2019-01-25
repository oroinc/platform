<?php

namespace Oro\Bundle\SoapBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Copies formatting rules from "fos_rest.format_negotiator" service to "oro_soap.listener.api_sub_request" service.
 * @see \FOS\RestBundle\DependencyInjection\Compiler\FormatListenerRulesPass
 * @see \Oro\Bundle\SoapBundle\EventListener\ApiSubRequestListener
 */
class ApiSubRequestPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $listenerDef = $container->getDefinition('oro_soap.listener.api_sub_request');
        $negotiatorDef = $container->getDefinition('fos_rest.format_negotiator');
        foreach ($negotiatorDef->getMethodCalls() as list($method, $arguments)) {
            if ('add' === $method) {
                $listenerDef->addMethodCall('addRule', $arguments);
            }
        }
    }
}
