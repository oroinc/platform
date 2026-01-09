<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that registers data writers with the writer chain service.
 *
 * This pass collects all services tagged with `oro_importexport.writer`,
 * extracts their alias from the tag, and adds them to the writer chain.
 * The writer chain uses these registered writers to determine which writer
 * should handle persisting data based on its alias.
 */
class WriterCompilerPass implements CompilerPassInterface
{
    #[\Override]
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
