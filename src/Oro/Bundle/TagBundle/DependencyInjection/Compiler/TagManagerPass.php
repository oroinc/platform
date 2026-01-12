<?php

namespace Oro\Bundle\TagBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that injects the {@see TagManager} service into all services tagged with `oro_tag.tag_manager`.
 *
 * This pass enables services to receive the {@see TagManager} dependency through a `setTagManager()` method call,
 * allowing them to manage tags on taggable entities without requiring direct constructor injection.
 */
class TagManagerPass implements CompilerPassInterface
{
    public const SERVICE_KEY = 'oro_tag.tag.manager';
    public const TAG = 'oro_tag.tag_manager';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $id => $tagAttributes) {
            $container->getDefinition($id)->addMethodCall('setTagManager', array(new Reference(self::SERVICE_KEY)));
        }
    }
}
