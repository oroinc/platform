<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers entity field handlers in the dependency injection container.
 *
 * This compiler pass collects all services tagged with `oro_entity.form.entity_field.handler`
 * and registers them with the entity field handler processor. It enables the system to process
 * entity field updates using pluggable handlers.
 */
class EntityFieldHandlerPass implements CompilerPassInterface
{
    public const HANDLER_PROCESSOR_SERVICE = 'oro_entity.form.entity_field.handler.processor.handler_processor';
    public const TAG = 'oro_entity.form.entity_field.handler';

    #[\Override]
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
