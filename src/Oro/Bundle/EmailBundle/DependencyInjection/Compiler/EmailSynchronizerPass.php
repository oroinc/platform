<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Collects all services tagged with "oro_email.email_synchronizer" and registers them
 * with the email synchronization manager, injecting the security token storage for authentication.
 */
class EmailSynchronizerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $selectorDef = $container->getDefinition('oro_email.email_synchronization_manager');
        $taggedServices = $container->findTaggedServiceIds('oro_email.email_synchronizer');
        foreach ($taggedServices as $synchronizerServiceId => $tagAttributes) {
            /**
             * We use the service id instead of the reference object
             * because of the service definition has scope "prototype"
             */
            $selectorDef->addMethodCall('addSynchronizer', [$synchronizerServiceId]);

            $container->getDefinition($synchronizerServiceId)
                ->addMethodCall('setTokenStorage', [new Reference(TokenStorageInterface::class)]);
        }
    }
}
