<?php

namespace Oro\Bundle\BlockBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds all services with the tags "oro_layout.block_type" as arguments of the "oro_layout.block_type_factory" service.
 */
class BlockTypePass implements CompilerPassInterface
{
    const SERVICE_BLOCK_TYPE_FACTORY_DI = 'oro_layout.block_type_factory';
    const SERVICE_BLOCK_TYPE = 'oro_layout.block_type';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_BLOCK_TYPE_FACTORY_DI)) {
            return;
        }

        $definition = $container->getDefinition(self::SERVICE_BLOCK_TYPE_FACTORY_DI);

        // Builds an array with tag aliases as keys and service IDs as values
        $types = array();

        foreach ($container->findTaggedServiceIds(self::SERVICE_BLOCK_TYPE) as $serviceId => $tag) {
            $alias = isset($tag[0]['alias'])
                ? $tag[0]['alias']
                : $serviceId;

            $types[$alias] = $serviceId;
        }

        $definition->replaceArgument(1, $types);
    }
}
