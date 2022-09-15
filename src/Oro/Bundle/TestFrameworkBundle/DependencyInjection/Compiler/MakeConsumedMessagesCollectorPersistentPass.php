<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\RegisterPersistentServicesPass;
use Oro\Component\MessageQueue\Test\Async\Extension\ConsumedMessagesCollectorExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds {@see ConsumedMessagesCollectorExtension} to the list of persistent services.
 */
class MakeConsumedMessagesCollectorPersistentPass extends RegisterPersistentServicesPass
{
    protected function getPersistentServices(ContainerBuilder $container): array
    {
        return ['oro_message_queue.test.async.extension.consumed_messages_collector'];
    }
}
