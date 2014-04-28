<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DeleteChannelProvidersPass implements CompilerPassInterface
{
    const DELETE_CHANNEL_PROVIDER_TAG = 'oro_integration.channel_delete_provider';
    const DELETE_CHANNEL_MANAGER = 'oro_integration.channel_delete_manager';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $providers = $container->findTaggedServiceIds(self::DELETE_CHANNEL_PROVIDER_TAG);
        if (!empty($providers)) {
            $definition = $container->getDefinition(
                self::DELETE_CHANNEL_MANAGER
            );
            foreach ($providers as $id => $attributes) {
                $definition->addMethodCall(
                    'addProvider',
                    [new Reference($id)]
                );
            }
        }
    }
}
