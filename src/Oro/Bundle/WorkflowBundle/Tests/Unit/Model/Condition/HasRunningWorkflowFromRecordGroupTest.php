<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Condition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\Condition\HasRunningWorkflowFromRecordGroup;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Provider\RunningWorkflowProvider;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HasRunningWorkflowFromRecordGroupTest extends TestCase
{
    private const GROUP_NAME = 'test_group_name';

    private WorkflowManager&MockObject $workflowManager;
    private RunningWorkflowProvider&MockObject $runningWorkflowProvider;
    private HasRunningWorkflowFromRecordGroup $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->runningWorkflowProvider = $this->createMock(RunningWorkflowProvider::class);

        $this->condition = new HasRunningWorkflowFromRecordGroup(
            $this->workflowManager,
            $this->runningWorkflowProvider
        );
    }

    private function getWorkflow(): Workflow
    {
        $definition = new WorkflowDefinition();
        $definition->setExclusiveRecordGroups([self::GROUP_NAME]);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $workflow;
    }

    public function testGetName(): void
    {
        $this->assertEquals('has_running_workflow_from_record_group', $this->condition->getName());
    }

    public function testEvaluateWithoutRunningWorkflow(): void
    {
        $entity = new \stdClass();
        $this->runningWorkflowProvider->expects(self::once())
            ->method('getRunningWorkflowNames')
            ->with(self::identicalTo($entity))
            ->willReturn([]);
        $this->workflowManager->expects(self::never())
            ->method('getWorkflow');

        self::assertSame(
            $this->condition,
            $this->condition->initialize(['group_name' => self::GROUP_NAME, 'entity' => $entity])
        );
        self::assertFalse($this->condition->evaluate([]));
    }

    public function testEvaluateWithRunningWorkflow(): void
    {
        $entity = new \stdClass();
        $this->runningWorkflowProvider->expects(self::once())
            ->method('getRunningWorkflowNames')
            ->with(self::identicalTo($entity))
            ->willReturn(['workflow1']);
        $this->workflowManager->expects(self::once())
            ->method('getWorkflow')
            ->with('workflow1')
            ->willReturn($this->getWorkflow());

        self::assertSame(
            $this->condition,
            $this->condition->initialize(['group_name' => self::GROUP_NAME, 'entity' => $entity])
        );
        self::assertTrue($this->condition->evaluate([]));
    }

    public function testEvaluateWithRunningWorkflowFromAnotherGroup(): void
    {
        $entity = new \stdClass();
        $this->runningWorkflowProvider->expects(self::once())
            ->method('getRunningWorkflowNames')
            ->with(self::identicalTo($entity))
            ->willReturn(['workflow1']);
        $this->workflowManager->expects(self::once())
            ->method('getWorkflow')
            ->with('workflow1')
            ->willReturn($this->getWorkflow());

        self::assertSame(
            $this->condition,
            $this->condition->initialize(['group_name' => 'another', 'entity' => $entity])
        );
        self::assertFalse($this->condition->evaluate([]));
    }

    public function testEvaluateWhenRunningWorkflowNotFound(): void
    {
        $entity = new \stdClass();
        $this->runningWorkflowProvider->expects(self::once())
            ->method('getRunningWorkflowNames')
            ->with(self::identicalTo($entity))
            ->willReturn(['workflow1']);
        $this->workflowManager->expects(self::once())
            ->method('getWorkflow')
            ->with('workflow1')
            ->willThrowException(new WorkflowException());

        self::assertSame(
            $this->condition,
            $this->condition->initialize(['group_name' => self::GROUP_NAME, 'entity' => $entity])
        );
        self::assertFalse($this->condition->evaluate([]));
    }

    /**
     * @dataProvider initializeExceptionProvider
     */
    public function testInitializeException(array $options, string $exception, string $exceptionMessage): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        $this->condition->initialize($options);
    }

    public function initializeExceptionProvider(): array
    {
        return [
            [
                'options' => [],
                'exception' => InvalidArgumentException::class,
                'exceptionMessage' => 'Group name parameter is required'
            ],
            [
                'options' => ['group_name' => 'test'],
                'exception' => InvalidArgumentException::class,
                'exceptionMessage' => 'Entity parameter is required'
            ]
        ];
    }
}
