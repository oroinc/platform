<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers import/export formatters.
 */
class FormatterProviderPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $formatters = [];
        $typeFormatters = [];
        $taggedServices = $container->findTaggedServiceIds('oro_importexport.formatter.formatter');
        foreach ($taggedServices as $id => $tags) {
            $formatters[$id] = new Reference($id);
            foreach ($tags as $attributes) {
                if (isset($attributes['data_type'])) {
                    if (!isset($attributes['format_type'])) {
                        throw new \InvalidArgumentException(\sprintf(
                            '"format_type" tag attribute must be defined for service "%s"',
                            $id
                        ));
                    }
                    $typeFormatters[$attributes['format_type']][$attributes['data_type']] = $id;
                }
            }
        }

        $container->getDefinition('oro_importexport.formatter.formatter_provider')
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $formatters))
            ->replaceArgument(1, $typeFormatters);
    }
}
