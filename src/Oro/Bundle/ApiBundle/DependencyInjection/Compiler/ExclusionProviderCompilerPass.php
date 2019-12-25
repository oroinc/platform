<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all entity exclusion providers that are used only in API.
 */
class ExclusionProviderCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $this->findAndSortTaggedServices('oro_entity.exclusion_provider.api', $container);
        if ($taggedServices) {
            $chainProviderDef = $container->getDefinition('oro_api.entity_exclusion_provider.shared');
            foreach ($taggedServices as $service) {
                $chainProviderDef->addMethodCall('addProvider', [$service]);
            }
        }
    }
}
