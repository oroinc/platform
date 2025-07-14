<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Provider\TransitionDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransitionDataProviderTest extends TestCase
{
    private WorkflowManager&MockObject $workflowManager;
    private TransitionDataProvider $transitionDataProvider;
    private Transition&MockObject $transition;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->transitionDataProvider = new TransitionDataProvider($this->workflowManager);

        $this->transition = $this->createMock(Transition::class);
        $this->transition->expects($this->any())
            ->method('isHidden')
            ->willReturn(false);
        $this->transition->expects($this->any())
            ->method('isEmptyInitOptions')
            ->willReturn(true);
        $this->transition->expects($this->any())
            ->method('getName')
            ->willReturn('test');
    }

    public function testGetAvailableStartTransitionsData(): void
    {
        $transitions = [$this->transition];
        $this->transition->expects($this->once())
            ->method('isEmptyInitOptions')
            ->willReturn(true);

        $workflowItem = $this->createMock(WorkflowItem::class);

        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('getStartTransitions')
            ->willReturn($transitions);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())
            ->method('isStartTransitionAvailable')
            ->willReturn(true);
        $workflow->expects($this->once())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);
        $workflow->expects($this->once())
            ->method('createWorkflowItem')
            ->willReturn($workflowItem);

        $data = $this->transitionDataProvider->getAvailableStartTransitionsData($workflow, new \stdClass(), false);

        $this->assertArrayHasKey('test', $data);
        $this->assertSame($data['test']['transition'], $this->transition);
    }

    public function testGetAvailableStartTransitionsDataEmptyData(): void
    {
        $this->transition->expects($this->once())
            ->method('isEmptyInitOptions')
            ->willReturn(true);

        $workflowItem = $this->createMock(WorkflowItem::class);

        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('getStartTransitions')
            ->willReturn([]);
        $transitionManager->expects($this->once())
            ->method('getDefaultStartTransition')
            ->willReturn($this->transition);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('isStartTransitionAvailable')
            ->willReturn(true);
        $workflow->expects($this->exactly(2))
            ->method('getTransitionManager')
            ->willReturn($transitionManager);
        $workflow->expects($this->once())
            ->method('createWorkflowItem')
            ->willReturn($workflowItem);

        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->once())
            ->method('hasStartStep')
            ->willReturn(true);
        $workflow->expects($this->once())
            ->method('getStepManager')
            ->willReturn($stepManager);

        $data = $this->transitionDataProvider->getAvailableStartTransitionsData($workflow, new \stdClass(), false);

        $this->assertArrayHasKey('test', $data);
        $this->assertSame($data['test']['transition'], $this->transition);
    }

    public function testGetAvailableTransitionsDataByWorkflowItem(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transitions = [$this->transition];
        $this->workflowManager->expects($this->once())
            ->method('isTransitionAvailable')
            ->willReturn(true);
        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->willReturn($transitions);

        $data = $this->transitionDataProvider->getAvailableTransitionsDataByWorkflowItem($workflowItem);

        $this->assertArrayHasKey('test', $data);
        $this->assertTrue($data['test']['isAllowed']);
        $this->assertSame($this->transition, $data['test']['transition']);
    }
}
