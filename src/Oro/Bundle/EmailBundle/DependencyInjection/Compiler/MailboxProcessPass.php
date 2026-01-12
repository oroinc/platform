<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers mailbox process providers in the dependency injection container.
 *
 * Collects all services tagged with `oro_email.mailbox_process` and registers them
 * with the mailbox process storage service for handling mailbox-specific processing.
 */
class MailboxProcessPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('oro_email.mailbox.process_storage')) {
            return;
        }

        $definition = $container->findDefinition(
            'oro_email.mailbox.process_storage'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'oro_email.mailbox_process'
        );

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall(
                    'addProcess',
                    [$attributes['type'], new Reference($id)]
                );
            }
        }
    }
}
