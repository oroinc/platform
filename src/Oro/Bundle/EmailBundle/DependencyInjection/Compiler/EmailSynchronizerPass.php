<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

        $tokenStorsgeDef = $container->getDefinition(self::SERVICE_TOKEN_STORAGE);

        $selectorDef    = $container->getDefinition(self::SERVICE_KEY);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $synchronizerServiceId => $tagAttributes) {
            $selectorDef->addMethodCall('addSynchronizer', array($synchronizerServiceId));

            $synchronizerDef = $container->getDefinition($synchronizerServiceId);
            $synchronizerDef->addMethodCall('setTokenStorage', [$tokenStorsgeDef]);
        }
    }
}
