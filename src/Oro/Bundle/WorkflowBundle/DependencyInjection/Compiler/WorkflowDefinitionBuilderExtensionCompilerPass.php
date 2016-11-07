<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class WorkflowDefinitionBuilderExtensionCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const WORKFLOW_DEFINITION_BUILDER_SERVICE_ID = 'oro_workflow.configuration.builder.workflow_definition';
    const WORKFLOW_DEFINITION_BUILDER_EXTENSION_TAG_NAME = 'oro.workflow.definition_builder.extension';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::WORKFLOW_DEFINITION_BUILDER_SERVICE_ID,
            self::WORKFLOW_DEFINITION_BUILDER_EXTENSION_TAG_NAME,
            'addExtension'
        );
    }
}
