<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Provider\TransitionDataProvider;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionDataManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var TransitionDataProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $transitionDataProvider;

    /** @var Transition|\PHPUnit_Framework_MockObject_MockObject */
    protected $transition;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();
        $this->transitionDataProvider = new TransitionDataProvider($this->workflowManager);

        $this->transition = $this->getMockBuilder(Transition::class)->disableOriginalConstructor()->getMock();
        $this->transition->expects($this->once())->method('isHidden')->willReturn(false);
        $this->transition->expects($this->any())->method('isEmptyInitOptions')->willReturn(true);
        $this->transition->expects($this->once())->method('getName')->willReturn('test');
    }

    public function testGetAvailableStartTransitionsData()
    {
        $this->transition->expects($this->once())->method('isEmptyInitOptions')->willReturn(true);

        $transitionManager = $this->getMockBuilder(TransitionManager::class)->disableOriginalConstructor()->getMock();
        $transitionManager->expects($this->once())
            ->method('getStartTransitions')
            ->willReturn([$this->transition]);

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->any())->method('isStartTransitionAvailable')->willReturn(true);
        $workflow->expects($this->once())->method('getTransitionManager')->willReturn($transitionManager);

        $data = $this->transitionDataProvider->getAvailableStartTransitionsData($workflow, new \stdClass(), false);

        $this->assertArrayHasKey('test', $data);
        $this->assertSame($data['test']['transition'], $this->transition);
    }

    public function testGetAvailableTransitionsDataByWorkflowItem()
    {
        $this->workflowManager->expects($this->once())->method('isTransitionAvailable')->willReturn(true);
        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->willReturn([$this->transition]);

        $data = $this->transitionDataProvider->getAvailableTransitionsDataByWorkflowItem(new WorkflowItem());

        $this->assertArrayHasKey('test', $data);
        $this->assertSame($data['test']['isAllowed'], true);
        $this->assertSame($data['test']['transition'], $this->transition);
    }
}
