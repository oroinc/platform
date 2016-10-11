<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WorkflowDefinitionBuilderCompilerPass implements CompilerPassInterface
{
    const WORKFLOW_DEFINITION_BUILDER_SERVICE_ID = 'oro_workflow.configuration.builder.workflow_definition';
    const WORKFLOW_DEFINITION_BUILDER_TAG_NAME = 'oro.workflow.workflow_definition.builder';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::WORKFLOW_DEFINITION_BUILDER_SERVICE_ID)) {
            return;
        }

        $handleBuilder = $container->getDefinition(self::WORKFLOW_DEFINITION_BUILDER_SERVICE_ID);

        $handlers = [];
        $taggedServices = $container->findTaggedServiceIds(self::WORKFLOW_DEFINITION_BUILDER_TAG_NAME);

        foreach ($taggedServices as $id => $attributes) {
            $priority = array_key_exists('priority', $attributes[0]) ? $attributes[0]['priority'] : 0;
            $handlers[$priority][] = new Reference($id);
        }

        ksort($handlers);

        array_walk_recursive($handlers, function ($handlerReference) use ($handleBuilder) {
            $handleBuilder->addMethodCall('addBuilder', [$handlerReference]);
        });
    }
}
