<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowDataSerializer;
use PHPUnit\Framework\MockObject\MockObject;

class WorkflowDataSerializerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowDataSerializer */
    protected $serializer;

    protected function setUp(): void
    {
        $this->serializer = new class() extends WorkflowDataSerializer {
            public function xgetWorkflowRegistry(): WorkflowRegistry
            {
                return $this->workflowRegistry;
            }
        };
    }

    protected function tearDown(): void
    {
        unset($this->serializer);
    }

    /**
     * @return WorkflowRegistry|MockObject
     */
    protected function createWorkflowRegistryMock()
    {
        return $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWorkflow'])
            ->getMock();
    }

    public function testSetWorkflowRegistry()
    {
        /** @var WorkflowRegistry $workflowRegistry */
        $workflowRegistry = $this->createWorkflowRegistryMock();
        $this->serializer->setWorkflowRegistry($workflowRegistry);
        static::assertEquals($workflowRegistry, $this->serializer->xgetWorkflowRegistry());
    }

    public function testSetWorkflowName()
    {
        static::assertEmpty($this->serializer->getWorkflowName());
        $workflowName = 'test_workflow';
        $this->serializer->setWorkflowName($workflowName);
        static::assertEquals($workflowName, $this->serializer->getWorkflowName());
    }

    public function testGetWorkflowName()
    {
        static::assertNull($this->serializer->getWorkflowName());
        $workflowName = 'test_workflow';
        $this->serializer->setWorkflowName($workflowName);
        static::assertEquals($workflowName, $this->serializer->getWorkflowName());
    }

    public function testGetWorkflow()
    {
        $workflowName = 'test_workflow';
        /** @var Workflow $workflow */
        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();

        $workflowRegistry = $this->createWorkflowRegistryMock();
        $workflowRegistry->expects(static::once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

        /** @var WorkflowRegistry $workflowRegistry */
        $this->serializer->setWorkflowRegistry($workflowRegistry);
        $this->serializer->setWorkflowName($workflowName);
        static::assertEquals($workflow, $this->serializer->getWorkflow());
    }
}
