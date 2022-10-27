<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all owner metadata providers.
 */
class OwnerMetadataProvidersPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = $this->findAndSortTaggedServices('oro_security.owner.metadata_provider', 'alias', $container);

        $chainProviderDef = $container->getDefinition('oro_security.owner.metadata_provider.chain');
        foreach ($services as $alias => $reference) {
            $chainProviderDef->addMethodCall('addProvider', [$alias, $reference]);
        }
    }
}
