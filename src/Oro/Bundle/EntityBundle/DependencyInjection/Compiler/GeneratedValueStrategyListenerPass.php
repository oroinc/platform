<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GeneratedValueStrategyListenerPass implements CompilerPassInterface
{
    const SERVICE_NAME = 'oro_entity.listener.orm.generated_value_strategy_listener';
    const DOCTRINE_CONNECTIONS_PARAM = 'doctrine.connections';

    /** {@inheritdoc} */
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
