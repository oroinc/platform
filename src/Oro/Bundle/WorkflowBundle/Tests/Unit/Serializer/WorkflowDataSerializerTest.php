<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowDataSerializer;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class WorkflowDataSerializerTest extends TestCase
{
    private WorkflowDataSerializer $serializer;

    #[\Override]
    protected function setUp(): void
    {
        $this->serializer = new WorkflowDataSerializer();
    }

    public function testSetWorkflowRegistry(): void
    {
        $workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->serializer->setWorkflowRegistry($workflowRegistry);
        self::assertEquals(
            $workflowRegistry,
            ReflectionUtil::getPropertyValue($this->serializer, 'workflowRegistry')
        );
    }

    public function testSetWorkflowName(): void
    {
        self::assertEmpty($this->serializer->getWorkflowName());
        $workflowName = 'test_workflow';
        $this->serializer->setWorkflowName($workflowName);
        self::assertEquals($workflowName, $this->serializer->getWorkflowName());
    }

    public function testGetWorkflowName(): void
    {
        self::assertNull($this->serializer->getWorkflowName());
        $workflowName = 'test_workflow';
        $this->serializer->setWorkflowName($workflowName);
        self::assertEquals($workflowName, $this->serializer->getWorkflowName());
    }

    public function testGetWorkflow(): void
    {
        $workflowName = 'test_workflow';
        $workflow = $this->createMock(Workflow::class);

        $workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $workflowRegistry->expects(self::once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

        $this->serializer->setWorkflowRegistry($workflowRegistry);
        $this->serializer->setWorkflowName($workflowName);
        self::assertEquals($workflow, $this->serializer->getWorkflow());
    }
}
