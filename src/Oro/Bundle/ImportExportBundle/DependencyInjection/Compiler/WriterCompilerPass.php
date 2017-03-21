<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WriterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro_importexport.writer.writer_chain')) {
            return;
        }

        $definition = $container->getDefinition('oro_importexport.writer.writer_chain');
        $taggedServices = $container->findTaggedServiceIds('oro_importexport.writer');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addWriter', [
                    new Reference($id),
                    $attributes['alias']
                ]);
            }
        }
    }
}
