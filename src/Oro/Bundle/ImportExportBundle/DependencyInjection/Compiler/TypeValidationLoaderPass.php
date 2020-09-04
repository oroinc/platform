<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds validation loader for import-export identity entity fields.
 */
class TypeValidationLoaderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('validator.builder')
            ->addMethodCall(
                'addLoader',
                [new Reference('oro_importexport.validator.type_validation_loader')]
            );
    }
}
