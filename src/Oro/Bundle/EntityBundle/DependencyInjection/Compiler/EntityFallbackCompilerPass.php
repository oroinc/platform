<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityFallbackCompilerPass implements CompilerPassInterface
{
    const RESOLVER_SERVICE = 'oro_entity.fallback.resolver.entity_fallback_resolver';
    const PROVIDER_TAG = 'oro_entity.fallback_provider';

    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::RESOLVER_SERVICE)) {
            return;
        }

        $resolver = $container->findDefinition(self::RESOLVER_SERVICE);
        $providers = $container->findTaggedServiceIds(self::PROVIDER_TAG);

        foreach ($providers as $id => $tags) {
            $tags = reset($tags);
            $resolver->addMethodCall('addFallbackProvider', [new Reference($id), $tags['id']]);
        }
    }
}
