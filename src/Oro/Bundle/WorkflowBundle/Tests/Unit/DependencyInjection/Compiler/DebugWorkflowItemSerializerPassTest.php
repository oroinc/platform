<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\DebugWorkflowItemSerializerPass;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem\DebugWorkflowItemSerializer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DebugWorkflowItemSerializerPassTest extends \PHPUnit\Framework\TestCase
{
    private DebugWorkflowItemSerializerPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new DebugWorkflowItemSerializerPass();
    }

    public function testProcessForNonDebugMode(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $this->compiler->process($container);

        self::assertFalse($container->hasDefinition('oro_workflow.debug_workflow_item_serializer'));
    }

    public function testProcessForDebugMode(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $this->compiler->process($container);


        $expectedService = new Definition(DebugWorkflowItemSerializer::class);
        $expectedService->setDecoratedService('oro_workflow.workflow_item_serializer', null, -255);
        $expectedService->setArguments([
            new Reference('.inner')
        ]);
        $expectedService->setPublic(false);

        self::assertEquals(
            $expectedService,
            $container->getDefinition('oro_workflow.debug_workflow_item_serializer')
        );
    }
}
