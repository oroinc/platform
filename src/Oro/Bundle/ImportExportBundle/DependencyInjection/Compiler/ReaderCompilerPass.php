<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ReaderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (! $container->hasDefinition('oro_importexport.reader.reader_chain')) {
            return;
        }

        $definition = $container->getDefinition('oro_importexport.reader.reader_chain');
        $taggedServices = $container->findTaggedServiceIds('oro_importexport.reader');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addReader', [
                    new Reference($id),
                    $attributes['alias']
                ]);
            }
        }
    }
}
