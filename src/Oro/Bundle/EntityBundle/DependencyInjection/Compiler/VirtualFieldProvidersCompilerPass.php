<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all providers of virtual fields.
 */
class VirtualFieldProvidersCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $providers = $this->findAndSortTaggedServices('oro_entity.virtual_field_provider', $container);
        $container->getDefinition('oro_entity.virtual_field_provider.chain')
            ->replaceArgument(0, $providers);
    }
}
