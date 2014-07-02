<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExclusionProviderPass implements CompilerPassInterface
{
    const TAG_NAME          = 'oro_entity.exclusion_provider.default';
    const CHAIN_PROVIDER_ID = 'oro_entity.exclusion_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::CHAIN_PROVIDER_ID)) {
            $chainProvider                = $container->getDefinition(self::CHAIN_PROVIDER_ID);
            $foundDefaultExcludeProviders = $container->findTaggedServiceIds(self::TAG_NAME);

            foreach ($foundDefaultExcludeProviders as $serviceId => $tags) {
                $ref = new Reference($serviceId);

                $chainProvider->addMethodCall('addProvider', [$ref]);
            }
        }
    }
}
