<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collect all origin providers by tag and add them to a provider chain
 */
class OriginProviderPass implements CompilerPassInterface
{
    private const CHAIN_SERVICE_ID = 'oro_sync.authentication.origin.origin_provider_chain';
    private const TAG_NAME = 'oro_sync.origin_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $chainDef = $container->getDefinition(self::CHAIN_SERVICE_ID);

        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $serviceId => $tag) {
            $chainDef->addMethodCall('addProvider', [new Reference($serviceId)]);
        }
    }
}
