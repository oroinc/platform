<?php

namespace Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AttributeBlockTypeMapperPass implements CompilerPassInterface
{
    const CHAIN_SERVICE = 'oro_entity_config.layout.chain_attribute_block_type_mapper';
    const TAG           = 'oro_entity_config.layout.attribute_block_type_mapper';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (0 === count($taggedServices)) {
            return;
        }

        $registryDefinition = $container->getDefinition(self::CHAIN_SERVICE);

        $priorities = [];

        /** @var array $tags */
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $priorities[$serviceId] = array_key_exists('priority', $attributes) ? $attributes['priority'] : 0;
            }
        }

        ksort($priorities);

        foreach ($priorities as $serviceId => $priority) {
            $registryDefinition->addMethodCall('addMapper', [new Reference($serviceId)]);
        }
    }
}
