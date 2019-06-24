<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects and register access rules.
 */
class AccessRulesPass implements CompilerPassInterface
{
    private const EXECUTOR_SERVICE_ID = 'oro_security.access_rule_executor';
    private const RULE_TAG_NAME       = 'oro_security.access_rule';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $serviceIds = [];
        $services = [];
        foreach ($container->findTaggedServiceIds(self::RULE_TAG_NAME, true) as $serviceId => $attributes) {
            $priority = $attributes[0]['priority'] ?? 0;
            $serviceIds[$priority][] = $serviceId;
            $services[$serviceId] = new Reference($serviceId);
        }
        if ($serviceIds) {
            krsort($serviceIds);
            $serviceIds = array_merge(...$serviceIds);
        }
        $container->findDefinition(self::EXECUTOR_SERVICE_ID)
            ->setArgument(0, $serviceIds)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
