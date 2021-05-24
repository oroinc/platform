<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all path providers.
 */
class ResourcePathProvidersPass implements CompilerPassInterface
{
    private const PROVIDER_SERVICE_ID = 'oro_layout.loader.path_provider';
    private const TAG_NAME = 'layout.resource.path_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $chainProviderDef = $container->getDefinition(self::PROVIDER_SERVICE_ID);
        $taggedServiceIds = $container->findTaggedServiceIds(self::TAG_NAME);
        foreach ($taggedServiceIds as $id => $attributes) {
            $chainProviderDef->addMethodCall(
                'addProvider',
                [new Reference($id), $attributes[0]['priority'] ?? 0]
            );
        }
    }
}
