<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers email synchronizers in the dependency injection container.
 *
 * Collects all services tagged with `oro_email.email_synchronizer` and registers them
 * with the email synchronization manager, injecting the security token storage for authentication.
 */
class EmailSynchronizerPass implements CompilerPassInterface
{
    public const SERVICE_KEY = 'oro_email.email_synchronization_manager';
    public const TAG         = 'oro_email.email_synchronizer';

    public const SERVICE_TOKEN_STORAGE = 'security.token_storage';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }

        $tokenStorageRef = new Reference(self::SERVICE_TOKEN_STORAGE);

        $selectorDef    = $container->getDefinition(self::SERVICE_KEY);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $synchronizerServiceId => $tagAttributes) {
            /**
             * We use the service id instead of the reference object
             * because of the service definition has scope "prototype"
             */
            $selectorDef->addMethodCall('addSynchronizer', [$synchronizerServiceId]);

            $synchronizerDef = $container->getDefinition($synchronizerServiceId);
            $synchronizerDef->addMethodCall('setTokenStorage', [$tokenStorageRef]);
        }
    }
}
