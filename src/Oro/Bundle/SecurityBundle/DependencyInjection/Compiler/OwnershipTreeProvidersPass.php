<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OwnershipTreeProvidersPass implements CompilerPassInterface
{
    const TAG_NAME = 'oro_security.ownership.tree_provider';
    const CHAIN_SERVICE_ID = 'oro_security.ownership_tree_provider.chain';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::CHAIN_SERVICE_ID)) {
            return;
        }

        $chainServiceDefinition = $container->getDefinition(self::CHAIN_SERVICE_ID);
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

        foreach (array_keys($taggedServices) as $id) {
            $chainServiceDefinition->addMethodCall('addProvider', [new Reference($id)]);
        }
    }
}
