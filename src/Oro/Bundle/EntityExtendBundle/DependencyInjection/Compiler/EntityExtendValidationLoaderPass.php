<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds validation loader for configurable entity fields.
 */
class EntityExtendValidationLoaderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('validator.builder')
            ->addMethodCall('addLoader', [new Reference('oro_entity_extend.validation_loader')]);
    }
}
