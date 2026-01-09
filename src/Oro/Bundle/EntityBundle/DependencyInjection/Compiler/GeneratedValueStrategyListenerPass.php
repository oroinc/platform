<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures the generated value strategy listener for all Doctrine connections.
 *
 * This compiler pass registers the generated value strategy listener with each configured
 * Doctrine connection. It ensures that the listener is properly attached to handle
 * loadClassMetadata events for all database connections in the application.
 */
class GeneratedValueStrategyListenerPass implements CompilerPassInterface
{
    public const SERVICE_NAME = 'oro_entity.listener.orm.generated_value_strategy_listener';
    public const DOCTRINE_CONNECTIONS_PARAM = 'doctrine.connections';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_NAME)) {
            return;
        }

        if (!$container->hasParameter(self::DOCTRINE_CONNECTIONS_PARAM)) {
            return;
        }

        $definition = $container->getDefinition(self::SERVICE_NAME);
        $definition->clearTag('doctrine.event_listener');

        $connections = array_keys((array)$container->getParameter(self::DOCTRINE_CONNECTIONS_PARAM));
        foreach ($connections as $connection) {
            $definition->addTag(
                'doctrine.event_listener',
                ['event' => 'loadClassMetadata', 'connection' => $connection]
            );
        }
    }
}
