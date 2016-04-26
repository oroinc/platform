<?php

namespace Oro\Bundle\CalendarBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RecurrenceStrategyCompilerPass implements CompilerPassInterface
{
    const TAG = 'oro_calendar.recurrence.strategy';
    const DELEGATE_STRATEGY_SERVICE = 'oro_calendar.recurrence.strategy.delegate';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::DELEGATE_STRATEGY_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (empty($taggedServices)) {
            return;
        }

        $definition = $container->getDefinition(self::DELEGATE_STRATEGY_SERVICE);
        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
