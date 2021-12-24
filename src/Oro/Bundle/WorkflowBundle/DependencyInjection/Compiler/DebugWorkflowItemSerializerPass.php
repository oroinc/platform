<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem\DebugWorkflowItemSerializer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers {@see \Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem\DebugWorkflowItemSerializer} in debug mode.
 */
class DebugWorkflowItemSerializerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('kernel.debug')) {
            return;
        }

        $container->register('oro_workflow.debug_workflow_item_serializer', DebugWorkflowItemSerializer::class)
            ->addArgument(new Reference('.inner'))
            // should be at the top of the decoration chain
            ->setDecoratedService('oro_workflow.workflow_item_serializer', null, -255)
            ->setPublic(false);
    }
}
