<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\RegisterPersistentServicesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds MQ collectors to the list of persistent services.
 */
class MakeMessageQueueCollectorPersistentPass extends RegisterPersistentServicesPass
{
    private const PERSISTENT_SERVICES = [
        'oro_message_queue.test.async.extension.consumed_messages_collector',
        'oro_message_queue.test.driver.message_collector',
    ];

    public function process(ContainerBuilder $container): void
    {
        parent::process($container);

        foreach (self::PERSISTENT_SERVICES as $serviceId) {
            $container
                ->findDefinition($serviceId)
                // Must be explicitly public to become persistent during MQ consumption.
                ->setPublic(true);
        }
    }

    protected function getPersistentServices(ContainerBuilder $container): array
    {
        return self::PERSISTENT_SERVICES;
    }
}
