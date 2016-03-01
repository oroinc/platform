<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class EntityFieldHandlerPass implements CompilerPassInterface
{
    const HANDLER_PROCESSOR_SERVICE = 'oro_entity.form.entity_field.handler.processor.handler_processor';
    const TAG = 'oro_entity.form.entity_field.handler';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::HANDLER_PROCESSOR_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (empty($taggedServices)) {
            return;
        }

        $definition = $container->getDefinition(self::HANDLER_PROCESSOR_SERVICE);

        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall(
                'addHandler',
                [new Reference($id)]
            );
        }
    }
}
