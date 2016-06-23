<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionSchedule;

use Doctrine\ORM\AbstractQuery;

use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ItemsFetcher;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\TransitionQueryFactory;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class ItemsFetcherTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var TransitionQueryFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryFactory;

    /** @var ItemsFetcher */
    protected $itemsFetcher;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryFactory = $this->getMockBuilder(
            'Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\TransitionQueryFactory'
        )->disableOriginalConstructor()->getMock();

        $this->itemsFetcher = new ItemsFetcher($this->queryFactory, $this->workflowManager);
    }

    public function testFetchWorkflowItemsIds()
    {
        /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject $workflow */
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()->getMock();
        /** @var TransitionManager|\PHPUnit_Framework_MockObject_MockObject $transitionManager */
        $transitionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\TransitionManager')
            ->disableOriginalConstructor()->getMock();
        /** @var AbstractQuery|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['getArrayResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $workflowName = 'test_workflow';
        $transitionName = 'test_transition';
        $dql = 'schedule_filter != null';

        $transition = (new Transition())->setScheduleFilter($dql);

        $this->workflowManager->expects($this->once())->method('getWorkflow')->with($workflowName)
            ->willReturn($workflow);

        $workflow->expects($this->once())->method('getTransitionManager')->willReturn($transitionManager);

        $transitionManager->expects($this->once())->method('getTransition')->with($transitionName)
            ->willReturn($transition);

        $this->queryFactory->expects($this->once())->method('create')->with(
            $workflow,
            $transitionName,
            $dql
        )->willReturn($query);

        $query->expects($this->once())->method('getArrayResult')->willReturn([['id' => 1], ['id' => 2]]);

        $this->assertEquals([1, 2], $this->itemsFetcher->fetchWorkflowItemsIds($workflowName, $transitionName));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Can't get transition by given identifier "test_nonexistent_transition"
     */
    public function testNoTransitionFoundException()
    {
        $workflowName = 'test_workflow';
        $transitionName = 'test_nonexistent_transition';

        /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject $workflow */
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()->getMock();
        /** @var TransitionManager|\PHPUnit_Framework_MockObject_MockObject $transitionManager */
        $transitionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\TransitionManager')
            ->disableOriginalConstructor()->getMock();

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

        $workflow->expects($this->once())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);

        $transitionManager->expects($this->once())
            ->method('getTransition')
            ->with($transitionName)
            ->willReturn(null);

        $this->itemsFetcher->fetchWorkflowItemsIds($workflowName, $transitionName);
    }
}
