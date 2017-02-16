<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SplitterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro_importexport.splitter.splitter_chain')) {
            return;
        }

        $definition = $container->getDefinition('oro_importexport.splitter.splitter_chain');
        $taggedServices = $container->findTaggedServiceIds('oro_importexport.splitter');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addSplitter', [
                    new Reference($id),
                    $attributes['alias']
                ]);
            }
        }
    }
}
