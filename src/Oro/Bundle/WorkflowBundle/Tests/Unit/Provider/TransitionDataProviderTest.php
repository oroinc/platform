<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Provider\TransitionDataProvider;

class TransitionDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowManager;

    /** @var TransitionDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $transitionDataProvider;

    /** @var Transition|\PHPUnit\Framework\MockObject\MockObject */
    protected $transition;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->transitionDataProvider = new TransitionDataProvider($this->workflowManager);

        $this->transition = $this->createMock(Transition::class);
        $this->transition->expects($this->any())->method('isHidden')->willReturn(false);
        $this->transition->expects($this->any())->method('isEmptyInitOptions')->willReturn(true);
        $this->transition->expects($this->any())->method('getName')->willReturn('test');
    }

    public function testGetAvailableStartTransitionsData()
    {
        $transitions = [$this->transition];
        $this->transition->expects($this->once())->method('isEmptyInitOptions')->willReturn(true);

        $workflowItem = $this->createMock(WorkflowItem::class);

        $transitionManager = $this->getMockBuilder(TransitionManager::class)->disableOriginalConstructor()->getMock();
        $transitionManager->expects($this->once())
            ->method('getStartTransitions')
            ->willReturn($transitions);

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->any())->method('isStartTransitionAvailable')->willReturn(true);
        $workflow->expects($this->once())->method('getTransitionManager')->willReturn($transitionManager);
        $workflow->expects($this->once())->method('createWorkflowItem')->willReturn($workflowItem);

        $data = $this->transitionDataProvider->getAvailableStartTransitionsData($workflow, new \stdClass(), false);

        $this->assertArrayHasKey('test', $data);
        $this->assertSame($data['test']['transition'], $this->transition);
    }

    public function testGetAvailableStartTransitionsDataEmptyData()
    {
        $this->transition->expects($this->once())->method('isEmptyInitOptions')->willReturn(true);

        $workflowItem = $this->createMock(WorkflowItem::class);

        $transitionManager = $this->getMockBuilder(TransitionManager::class)->disableOriginalConstructor()->getMock();
        $transitionManager->expects($this->once())->method('getStartTransitions')->willReturn([]);
        $transitionManager->expects($this->once())->method('getDefaultStartTransition')->willReturn($this->transition);

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->once())->method('isStartTransitionAvailable')->willReturn(true);
        $workflow->expects($this->exactly(2))->method('getTransitionManager')->willReturn($transitionManager);
        $workflow->expects($this->once())->method('createWorkflowItem')->willReturn($workflowItem);

        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->once())->method('hasStartStep')->willReturn(true);
        $workflow->expects($this->once())->method('getStepManager')->willReturn($stepManager);

        $data = $this->transitionDataProvider->getAvailableStartTransitionsData($workflow, new \stdClass(), false);

        $this->assertArrayHasKey('test', $data);
        $this->assertSame($data['test']['transition'], $this->transition);
    }

    public function testGetAvailableTransitionsDataByWorkflowItem()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transitions = [$this->transition];
        $this->workflowManager->expects($this->once())->method('isTransitionAvailable')->willReturn(true);
        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->willReturn($transitions);

        $data = $this->transitionDataProvider->getAvailableTransitionsDataByWorkflowItem($workflowItem);

        $this->assertArrayHasKey('test', $data);
        $this->assertSame($data['test']['isAllowed'], true);
        $this->assertSame($data['test']['transition'], $this->transition);
    }
}
