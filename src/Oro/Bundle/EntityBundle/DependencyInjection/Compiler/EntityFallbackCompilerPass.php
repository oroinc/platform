<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityFallbackCompilerPass implements CompilerPassInterface
{
    const RESOLVER_SERVICE = 'oro_entity.fallback.resolver.entity_fallback_resolver';
    const PROVIDER_TAGS = 'oro.fallback.provider';

    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::RESOLVER_SERVICE)) {
            return;
        }

        $resolver = $container->findDefinition(self::RESOLVER_SERVICE);
        $providers = $container->findTaggedServiceIds(self::PROVIDER_TAGS);

        foreach ($providers as $id => $tags) {
            $provider = $container->findDefinition($id);
            $tags = reset($tags);
            $provider->addMethodCall('setId', [$tags['id']]);
            $resolver->addMethodCall('addFallbackProvider', [new Reference($id)]);
        }
    }
}
