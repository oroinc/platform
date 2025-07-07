<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * This compiler pass is responsible for collecting all PDF document operators and registering them in the
 * `oro_pdf_generator.pdf_document.operator.registry` service.
 *
 * The PDF document operators are tagged with `oro_pdf_generator.pdf_document.operator` and must define the
 * `entity_class` and `mode` attributes in their tags.
 */
final class PdfDocumentOperatorRegistryPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $pdfDocumentOperatorRegistryId = 'oro_pdf_generator.pdf_document.operator.registry';

        if (!$container->has($pdfDocumentOperatorRegistryId)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds('oro_pdf_generator.pdf_document.operator');
        $operatorsByEntityClassAndMode = [];

        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $entityClass = $attributes['entity_class'] ?? null;
                $mode = $attributes['mode'] ?? null;

                if (!$entityClass || !$mode) {
                    throw new \InvalidArgumentException(sprintf(
                        'The service "%s" must define "entity_class" and "mode" attributes in its tag.',
                        $serviceId
                    ));
                }

                $operatorsByEntityClassAndMode[$entityClass][$mode] = new Reference($serviceId);
            }
        }

        $operatorsByEntityClassLocator = [];
        foreach ($operatorsByEntityClassAndMode as $entityClass => $operatorsByMode) {
            foreach ($operatorsByMode as $mode => $operator) {
                $operatorsByMode[$mode] = new ServiceClosureArgument($operator);
            }

            $operatorsByEntityClassLocator[$entityClass] = new Definition(ServiceLocator::class, [$operatorsByMode]);
        }

        $container
            ->findDefinition($pdfDocumentOperatorRegistryId)
            ->setArgument('$pdfDocumentOperatorLocator', new ServiceLocatorArgument($operatorsByEntityClassLocator));
    }
}
