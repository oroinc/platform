<?php

namespace Oro\Bundle\SoapBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that registers metadata providers in the dependency injection container.
 *
 * Collects all services tagged with `oro_soap.metadata_provider` and adds them to the
 * chain metadata provider service, enabling extensible metadata retrieval for API objects.
 */
class MetadataProvidersPass implements CompilerPassInterface
{
    const TAG_NAME = 'oro_soap.metadata_provider';
    const CHAIN_SERVICE_ID = 'oro_soap.provider.metadata.chain';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::CHAIN_SERVICE_ID)) {
            return;
        }

        $chainServiceDefinition = $container->getDefinition(self::CHAIN_SERVICE_ID);
        $tagged                 = $container->findTaggedServiceIds(self::TAG_NAME);
        foreach (array_keys($tagged) as $serviceId) {
            $chainServiceDefinition->addMethodCall('addProvider', [new Reference($serviceId)]);
        }
    }
}
