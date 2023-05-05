<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Overrides 'property_accessor' service to create it with our factory
 */
class ChangePropertyAccessorReflectionExtractorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('property_accessor')
            ->setFactory([
                $container->getDefinition('oro_entity_extend.accessor.property_access_factory'),
                'createPropertyAccessor'
            ]);
    }
}
