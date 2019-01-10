<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all providers of virtual relations.
 */
class VirtualRelationProvidersCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $providers = $this->findAndSortTaggedServices('oro_entity.virtual_relation_provider', $container);
        $container->getDefinition('oro_entity.virtual_relation_provider.chain')
            ->replaceArgument(0, $providers);
    }
}
