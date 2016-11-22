<?php

namespace Oro\Bundle\ActivityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ActivityEntityDeleteHandlerCompilerPass implements CompilerPassInterface
{
    const TAG_NAME = 'oro_activity.activity_entity_delete_handler';
    const REGISTRY_SERVICE_ID = 'oro_activity.handler.delete.activity_entity_registry';
    const ADD_ADAPTER_METHOD = 'addActivityEntityDeleteHandler';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $registryDefinition = $container->findDefinition(self::REGISTRY_SERVICE_ID);
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $tags) {
            foreach ($tags as $tag) {
                $priority = isset($tag['priority']) ? $tag['priority'] : 0;
                $registryDefinition->addMethodCall(self::ADD_ADAPTER_METHOD, [new Reference($id), $priority]);
            }
        }
    }
}
