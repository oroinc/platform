<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\RegisterPersistentServicesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds {@see \Symfony\Component\Mailer\EventListener\MessageLoggerListener} to the list of persistent services.
 */
class MakeMailerMessageLoggerListenerPersistentPass extends RegisterPersistentServicesPass
{
    public function process(ContainerBuilder $container): void
    {
        parent::process($container);

        $container
            ->findDefinition('mailer.message_logger_listener')
            // Must be explicitly public to become persistent during MQ consumption.
            ->setPublic(true);
    }

    protected function getPersistentServices(ContainerBuilder $container): array
    {
        return ['mailer.message_logger_listener'];
    }
}
