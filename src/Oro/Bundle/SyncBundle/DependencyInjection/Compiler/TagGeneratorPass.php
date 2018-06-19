<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TagGeneratorPass implements CompilerPassInterface
{
    private const TAG_GENERATOR_SERVICE_ID = 'oro_sync.content.tag_generator';
    private const TAG_NAME = 'oro_sync.tag_generator';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::TAG_GENERATOR_SERVICE_ID)) {
            return;
        }

        $tagGeneratorDefinition = $container->getDefinition(self::TAG_GENERATOR_SERVICE_ID);
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $serviceId => $tags) {
            $tagGeneratorDefinition->addMethodCall('addGenerator', [new Reference($serviceId)]);
        }
    }
}
