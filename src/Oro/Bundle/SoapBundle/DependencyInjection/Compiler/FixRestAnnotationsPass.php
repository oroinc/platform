<?php

namespace Oro\Bundle\SoapBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Replace annotation readers to sets name="" for FOS Rest routing annotations.
 * @link https://github.com/FriendsOfSymfony/FOSRestBundle/issues/1086
 */
class FixRestAnnotationsPass implements CompilerPassInterface
{
    const ROUTING_LOADER_SERVICE = 'sensio_framework_extra.routing.loader.annot_class';
    const REST_ROUTING_LOADER_SERVICE = 'fos_rest.routing.loader.reader.action';
    const ANNOTATION_READER_SERVICE_SUFFIX = '.annotation_reader';
    const ANNOTATION_READER_CLASS = 'Oro\Bundle\SoapBundle\Routing\RestAnnotationReader';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has(self::ROUTING_LOADER_SERVICE) && $container->has(self::REST_ROUTING_LOADER_SERVICE)) {
            $this->replaceAnnotationReader(
                $container,
                self::ROUTING_LOADER_SERVICE,
                0
            );
            $this->replaceAnnotationReader(
                $container,
                self::REST_ROUTING_LOADER_SERVICE,
                0
            );
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $routingLoaderServiceId
     * @param string           $annotationReaderArgumentId
     */
    protected function replaceAnnotationReader(
        ContainerBuilder $container,
        $routingLoaderServiceId,
        $annotationReaderArgumentId
    ) {
        $routingLoaderDef = $container->getDefinition($routingLoaderServiceId);

        $annotationReaderServiceId = $routingLoaderServiceId . self::ANNOTATION_READER_SERVICE_SUFFIX;

        $annotationReaderDef = new Definition(
            self::ANNOTATION_READER_CLASS,
            [$routingLoaderDef->getArgument($annotationReaderArgumentId)]
        );
        $annotationReaderDef->setPublic(false);

        $container->addDefinitions([$annotationReaderServiceId => $annotationReaderDef]);

        $routingLoaderDef->replaceArgument(
            $annotationReaderArgumentId,
            new Reference($annotationReaderServiceId)
        );
    }
}
