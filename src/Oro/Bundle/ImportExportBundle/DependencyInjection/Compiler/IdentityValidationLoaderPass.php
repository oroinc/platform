<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds validation loader for import-export identity entity fields.
 */
class IdentityValidationLoaderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('validator.builder')
            ->addMethodCall(
                'addLoader',
                [new Reference('oro_importexport.validator.identity_validation_loader')]
            );
    }
}
