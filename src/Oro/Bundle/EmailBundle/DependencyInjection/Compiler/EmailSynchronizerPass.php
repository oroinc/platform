<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EmailSynchronizerPass implements CompilerPassInterface
{
    const SERVICE_KEY = 'oro_email.email_synchronization_manager';
    const TAG         = 'oro_email.email_synchronizer';

    const SERVICE_TOKEN_STORAGE = 'security.token_storage';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }

        $selectorDef    = $container->getDefinition(self::SERVICE_KEY);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $synchronizerServiceId => $tagAttributes) {
            $selectorDef->addMethodCall('addSynchronizer', [new Reference($synchronizerServiceId, 1, false)]);

            $synchronizerDef = $container->getDefinition($synchronizerServiceId);
            $synchronizerDef->addMethodCall('setTokenStorage', [new Reference(self::SERVICE_TOKEN_STORAGE)]);
        }
    }
}
