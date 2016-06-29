<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionSchedule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
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

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

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

        $this->entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemsFetcher = new ItemsFetcher($this->queryFactory, $this->workflowManager, $this->entityRepository);
    }

    /**
     * @dataProvider fetchWorkflowItemsIdsDataProvider
     *
     * @param bool $checkConditions
     * @param bool $isFirstAllowed
     * @param array $expected
     */
    public function testFetchWorkflowItemsIds($checkConditions, $isFirstAllowed, array $expected)
    {
        $workflowName = 'test_workflow';
        $transitionName = 'test_transition';
        /** @var AbstractQuery|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['getArrayResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->queryFactory->expects($this->once())->method('create')->with(
            ['step1', 'step2'],
            'EntityClass',
            'schedule_filter != null'
        )->willReturn($query);

        $query->expects($this->once())->method('getArrayResult')->willReturn([['id' => 1], ['id' => 2]]);
        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->getMock('Oro\Bundle\WorkflowBundle\Model\Transition');
        $transition->expects($this->once())->method('getScheduleFilter')->willReturn('schedule_filter != null');
        $transition->expects($this->once())->method('isScheduleCheckConditions')->willReturn($checkConditions);

        $this->prepareMocks($workflowName, $transitionName, $transition);
        if ($checkConditions) {
            $workflowItem1 = (new WorkflowItem())->setId(1);
            $workflowItem2 = (new WorkflowItem())->setId(2);
            $transition->expects($this->exactly(2))->method('isAllowed')->willReturnMap(
                [
                    [$workflowItem1, null, $isFirstAllowed],
                    [$workflowItem2, null, true],
                ]
            );

            $this->entityRepository
                ->expects($this->once())
                ->method('findBy')
                ->willReturn([$workflowItem1,$workflowItem2]);
        }

        $this->assertEquals($expected, $this->itemsFetcher->fetchWorkflowItemsIds($workflowName, $transitionName));
    }

    /**
     * @return array
     */
    public function fetchWorkflowItemsIdsDataProvider()
    {
        return [
            'no check, not allowed' => [
                'checkConditions' => false,
                'isFirstAllowed' => false,
                'expected' => [1, 2],
            ],
            'check, not allowed' => [
                'checkConditions' => true,
                'isFirstAllowed' => false,
                'expected' => [2],
            ],
            'no check, allowed' => [
                'checkConditions' => false,
                'isFirstAllowed' => true,
                'expected' => [1, 2],
            ],
            'check, allowed' => [
                'checkConditions' => true,
                'isFirstAllowed' => true,
                'expected' => [1, 2],
            ],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cant get transition by given identifier "test_nonexistent_transition"
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

    /**
     * @param string $workflowName
     * @param string $transitionName
     * @param Transition $transition
     */
    private function prepareMocks($workflowName, $transitionName, Transition $transition)
    {
        /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject $workflow */
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()->getMock();
        /**@var StepManager|\PHPUnit_Framework_MockObject_MockObject $stepManager */
        $stepManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\StepManager')
            ->disableOriginalConstructor()->getMock();
        /** @var WorkflowDefinition|\PHPUnit_Framework_MockObject_MockObject $workflowDefinition */
        $workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()->getMock();
        /** @var TransitionManager|\PHPUnit_Framework_MockObject_MockObject $transitionManager */
        $transitionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\TransitionManager')
            ->disableOriginalConstructor()->getMock();

        $this->workflowManager->expects($this->once())->method('getWorkflow')->with($workflowName)
            ->willReturn($workflow);

        $workflow->expects($this->once())->method('getTransitionManager')->willReturn($transitionManager);
        $workflow->expects($this->once())->method('getStepManager')->willReturn($stepManager);
        $workflow->expects($this->once())->method('getDefinition')->willReturn($workflowDefinition);

        $transitionManager->expects($this->once())->method('getTransition')->with($transitionName)
            ->willReturn($transition);

        $step1 = (new Step())->setName('step1');
        $step2 = (new Step())->setName('step2');

        $stepManager->expects($this->once())->method('getRelatedTransitionSteps')
            ->with($transitionName)
            ->willReturn(new ArrayCollection([$step1, $step2]));

        $workflowDefinition->expects($this->once())->method('getRelatedEntity')->willReturn('EntityClass');
    }
}
