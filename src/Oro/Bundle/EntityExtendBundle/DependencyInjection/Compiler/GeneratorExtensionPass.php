<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GeneratorExtensionPass implements CompilerPassInterface
{
    const GENERATOR_NAME = 'oro_entity_extend.entity_generator';
    const GENERATOR_TAG  = 'oro_entity_extend.generator_extension';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::GENERATOR_NAME)) {
            return;
        }

        $generatorDefinition = $container->getDefinition(self::GENERATOR_NAME);
        $taggedServices = $container->findTaggedServiceIds(self::GENERATOR_TAG);

        foreach ($taggedServices as $id => $tagAttributes) {
            $params = [new Reference($id)];
            if (!empty($tagAttributes['priority'])) {
                $params[] = (int) $tagAttributes['priority'];
            }

            $generatorDefinition->addMethodCall('addExtension', $params);
        }
    }
}
