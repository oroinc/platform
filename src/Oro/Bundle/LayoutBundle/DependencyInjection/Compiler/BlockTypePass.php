<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds all services with the tags "layout.block_type" as arguments of the "oro_layout.block_type_factory" service.
 */
class BlockTypePass implements CompilerPassInterface
{
    const FACTORY_SERVICE_ID = 'oro_layout.block_type_factory';
    const TAG_NAME = 'layout.block_type';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::FACTORY_SERVICE_ID)) {
            return;
        }

        $types = array();
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $serviceId => $tag) {
            $alias = isset($tag[0]['alias'])
                ? $tag[0]['alias']
                : $serviceId;

            $types[$alias] = $serviceId;
        }

        $factoryDef = $container->getDefinition(self::FACTORY_SERVICE_ID);
        $factoryDef->replaceArgument(1, $types);
    }
}
