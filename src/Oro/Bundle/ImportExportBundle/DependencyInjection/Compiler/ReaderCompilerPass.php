<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that registers data readers with the reader chain service.
 *
 * This pass collects all services tagged with `oro_importexport.reader`,
 * extracts their alias from the tag, and adds them to the reader chain.
 * The reader chain uses these registered readers to determine which reader
 * should handle a particular data source based on its alias.
 */
class ReaderCompilerPass implements CompilerPassInterface
{
    #[\Override]
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
