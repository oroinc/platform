<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds validation loader for workflow entities.
 */
class AddWorkflowValidationLoaderCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('validator.builder')
            ->addMethodCall('addLoader', [new Reference('oro_workflow.validation_loader')]);
    }
}
