<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Doctrine\ORM\AbstractQuery;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\TransitionTriggerCronHelper;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionQueryFactory;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionTriggerCronHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var TransitionQueryFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryFactory;

    /** @var TransitionTriggerCron */
    protected $trigger;

    /** @var TransitionTriggerCronHelper */
    protected $helper;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryFactory = $this
            ->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionQueryFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName('test_workflow');
        $this->trigger = new TransitionTriggerCron();
        $this->trigger->setWorkflowDefinition($workflowDefinition);
        $this->trigger->setTransitionName('test_transition');

        $this->helper = new TransitionTriggerCronHelper($this->queryFactory, $this->workflowManager);
    }

    public function testFetchWorkflowItemsIds()
    {
        $workflowName = 'test_workflow';
        $transitionName = 'test_transition';
        $filter = 'filter != null';
        $this->trigger->setFilter($filter);

        /** @var AbstractQuery|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['getArrayResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $query->expects($this->once())->method('getArrayResult')->willReturn([['id' => 1], ['id' => 2]]);

        $workflow = $this->prepareMocks($workflowName);

        $this->queryFactory->expects($this->once())
            ->method('create')
            ->with($workflow, $transitionName, $filter)
            ->willReturn($query);

        $this->assertEquals([1, 2], $this->helper->fetchWorkflowItemsIdsForTrigger($this->trigger));
    }

    /**
     * @param string $workflowName
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareMocks($workflowName)
    {
        /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject $workflow */
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowManager->expects($this->once())->method('getWorkflow')->with($workflowName)
            ->willReturn($workflow);

        return $workflow;
    }
}
