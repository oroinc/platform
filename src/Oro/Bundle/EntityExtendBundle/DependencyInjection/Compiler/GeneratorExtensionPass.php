<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GeneratorExtensionPass implements CompilerPassInterface
{
    const DUMPER_NAME = 'oro_entity_extend.tools.dumper';
    const GENERATOR_TAG = 'oro_entity_extend.generator_extension';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::DUMPER_NAME)) {
            return;
        }

        $dumperDefinition = $container->getDefinition(self::DUMPER_NAME);
        $taggedServices = $container->findTaggedServiceIds(self::GENERATOR_TAG);

        foreach ($taggedServices as $id => $tagAttributes) {
            $params = [new Reference($id)];
            $dumperDefinition->addMethodCall('addGeneratorExtension', $params);
        }
    }
}
