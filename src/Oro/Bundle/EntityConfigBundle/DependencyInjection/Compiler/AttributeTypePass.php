<?php

namespace Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AttributeTypePass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const TAG = 'oro_entity_config.attribute_type';
    const SERVICE_ID = 'oro_entity_config.registry.attribute_type';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container, self::SERVICE_ID, self::TAG, 'addAttributeType');
    }
}
