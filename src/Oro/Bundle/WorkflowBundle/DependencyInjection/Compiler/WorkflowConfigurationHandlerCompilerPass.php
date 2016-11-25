<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class WorkflowConfigurationHandlerCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const WORKFLOW_CONFIGURATION_HANDLER_TAG_NAME = 'oro.workflow.configuration.handler';
    const DEFINITION_HANDLE_BUILDER_SERVICE_ID = 'oro_workflow.configuration.builder.workflow_definition.handle';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::DEFINITION_HANDLE_BUILDER_SERVICE_ID,
            self::WORKFLOW_CONFIGURATION_HANDLER_TAG_NAME,
            'addHandler'
        );
    }
}
