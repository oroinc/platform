<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\RegisterPersistentServicesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds {@see \Symfony\Component\Mailer\EventListener\MessageLoggerListener} to the list of persistent services.
 */
class MakeMailerMessageLoggerListenerPersistentPass extends RegisterPersistentServicesPass
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        parent::process($container);

        if ($container->hasDefinition('mailer.message_logger_listener')) {
            $container
                ->findDefinition('mailer.message_logger_listener')
                // Must be explicitly public to become persistent during MQ consumption.
                ->setPublic(true);
        }
    }

    #[\Override]
    protected function getPersistentServices(ContainerBuilder $container): array
    {
        if ($container->hasDefinition('mailer.message_logger_listener')) {
            return ['mailer.message_logger_listener'];
        }

        return [];
    }
}
