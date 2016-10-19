<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WorkflowConfigurationHandlerCompilerPass implements CompilerPassInterface
{
    const WORKFLOW_CONFIGURATION_HANDLER_TAG_NAME = 'oro.workflow.configuration.handler';
    const DEFINITION_HANDLE_BUILDER_SERVICE_ID = 'oro_workflow.configuration.builder.workflow_definition.handle';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::DEFINITION_HANDLE_BUILDER_SERVICE_ID)) {
            return;
        }

        $handleBuilder = $container->getDefinition(self::DEFINITION_HANDLE_BUILDER_SERVICE_ID);

        $handlers = [];
        $taggedServices = $container->findTaggedServiceIds(self::WORKFLOW_CONFIGURATION_HANDLER_TAG_NAME);

        foreach ($taggedServices as $id => $attributes) {
            $priority = array_key_exists('priority', $attributes[0]) ? $attributes[0]['priority'] : 0;
            $handlers[$priority][] = new Reference($id);
        }

        ksort($handlers);

        array_walk_recursive($handlers, function ($handlerReference) use ($handleBuilder) {
            $handleBuilder->addMethodCall('addHandler', [$handlerReference]);
        });
    }
}
