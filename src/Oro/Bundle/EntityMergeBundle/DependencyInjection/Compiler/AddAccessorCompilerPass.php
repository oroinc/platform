<?php

namespace Oro\Bundle\EntityMergeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddAccessorCompilerPass implements CompilerPassInterface
{
    const ACCESSOR_TAG = 'oro_entity_merge.accessor';
    const DELEGATE_ACCESSOR_SERVICE = 'oro_entity_merge.accessor.delegate';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $normalizerDefinition = $container->getDefinition(self::DELEGATE_ACCESSOR_SERVICE);
        foreach ($container->findTaggedServiceIds(self::ACCESSOR_TAG) as $id => $attributes) {
            $normalizerDefinition->addMethodCall('add', array(new Reference($id)));
        }
    }
}
