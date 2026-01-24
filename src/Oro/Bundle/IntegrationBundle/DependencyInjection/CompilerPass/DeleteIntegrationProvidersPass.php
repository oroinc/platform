<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers delete providers with the delete manager during dependency injection compilation.
 *
 * This compiler pass collects all services tagged with `oro_integration.delete_provider`
 * and registers them with the delete manager service. This allows the delete manager to
 * coordinate deletion operations across multiple providers, enabling custom deletion logic
 * for different integration types.
 */
class DeleteIntegrationProvidersPass implements CompilerPassInterface
{
    const DELETE_PROVIDER_TAG = 'oro_integration.delete_provider';
    const DELETE_MANAGER      = 'oro_integration.delete_manager';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $providers = $container->findTaggedServiceIds(self::DELETE_PROVIDER_TAG);
        if (!empty($providers)) {
            $definition = $container->getDefinition(
                self::DELETE_MANAGER
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
