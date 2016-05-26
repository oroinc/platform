<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class SwiftMailerEventCompilerPass implements CompilerPassInterface
{
    const SERVICE_ALIAS = 'oro_notification.mailer.transport.eventdispatcher';
    
    const TAGGED_SERVICE_NAME = 'swiftmailer.mailer.db_spool_mailer.plugin';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_ALIAS)) {
            return;
        }

        $definition = $container->getDefinition(self::SERVICE_ALIAS);

        $taggedServices = $container->findTaggedServiceIds(self::TAGGED_SERVICE_NAME);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'bindEventListener',
                [$container->getDefinition($id)]
            );
        }
    }
}
