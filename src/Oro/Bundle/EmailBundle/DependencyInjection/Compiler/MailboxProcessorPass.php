<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MailboxProcessorPass implements CompilerPassInterface
{

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('oro_email.provider.mailbox_processor_provider')) {
            return;
        }

        $definition = $container->findDefinition(
            'oro_email.provider.mailbox_processor_provider'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'oro_email.mailbox_processor'
        );

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall(
                    'addProcessorType',
                    [new Reference($id), $attributes['type']]
                );
            }
        }
    }
}
