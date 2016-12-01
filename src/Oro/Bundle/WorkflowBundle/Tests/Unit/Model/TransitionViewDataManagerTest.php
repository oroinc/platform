<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\TransitionViewDataManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionViewDataManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var TransitionViewDataManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $transitionViewDataManager;

    /** @var Transition|\PHPUnit_Framework_MockObject_MockObject */
    protected $transition;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->transitionViewDataManager = new TransitionViewDataManager($this->workflowManager);

        $this->transition = $this->createMockWithoutConstructor(Transition::class);
        $this->transition->expects($this->once())->method('isHidden')->willReturn(false);
        $this->transition->expects($this->any())->method('isEmptyInitOptions')->willReturn(true);
        $this->transition->expects($this->once())->method('getName')->willReturn('test');

    }

    /**
     * @dataProvider startTransitionDataProvider
     *
     * @param Workflow|\PHPUnit_Framework_MockObject_MockObject $workflow
     * @param bool $isHasInitOptions
     */
    public function testGetAvailableStartTransitionsData(Workflow $workflow, $isHasInitOptions)
    {
        $this->transition->expects($this->once())
            ->method('isEmptyInitOptions')
            ->willReturn($isHasInitOptions);
        $transitionManager = $this->createMockWithoutConstructor(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('getStartTransitions')
            ->willReturn([$this->transition]);
        $workflow->expects($this->once())->method('getTransitionManager')
            ->willReturn($transitionManager);

        $data = $this->transitionViewDataManager->getAvailableStartTransitionsData($workflow, new \stdClass());

        if (!$isHasInitOptions) {
            $this->assertArrayHasKey('test', $data);
            $this->assertSame($data['test']['transition'], $this->transition);
        } else {
            $this->assertEmpty($data);
        }
    }


    public function startTransitionDataProvider()
    {
        $workflow = $this->createMockWithoutConstructor(Workflow::class);
        $workflow->expects($this->any())->method('isStartTransitionAvailable')->willReturn(true);

        yield [
            'workflow' => $workflow,
            'transitionInitOptions' => false,
        ];

        $workflow = clone $workflow;
        $workflow->expects($this->once())->method('getStepManager')
            ->willReturn($this->createMockWithoutConstructor(StepManager::class));

        yield [
            'workflow' => $workflow,
            'transitionInitOptions' => false,
        ];
    }

    public function testGetAvailableTransitionsDataByWorkflowItem()
    {
        $this->workflowManager->expects($this->once())->method('isTransitionAvailable')->willReturn(true);

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->willReturn([$this->transition]);

        $data = $this->transitionViewDataManager->getAvailableTransitionsDataByWorkflowItem(
            $this->createMockWithoutConstructor(WorkflowItem::class)
        );

        $this->assertArrayHasKey('test', $data);
        $this->assertSame($data['test']['isAllowed'], true);
        $this->assertSame($data['test']['transition'], $this->transition);
    }

    /**
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockWithoutConstructor($className)
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }
}
