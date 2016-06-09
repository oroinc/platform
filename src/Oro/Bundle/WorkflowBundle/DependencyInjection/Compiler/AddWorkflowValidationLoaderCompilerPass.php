<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddWorkflowValidationLoaderCompilerPass implements CompilerPassInterface
{
    const WORKFLOW_VALIDATION_LOADER_ID = 'oro_workflow.validation_loader';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('validator.builder')) {
            $validatorBuilder = $container->getDefinition('validator.builder');
            $validatorBuilder->addMethodCall('addCustomLoader', [new Reference(self::WORKFLOW_VALIDATION_LOADER_ID)]);
        }
    }
}
