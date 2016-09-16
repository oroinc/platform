<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\TransitionTriggerCronHelper;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionTriggerCronHelperTest extends \PHPUnit_Framework_TestCase
{
    const TRANSITION_NAME = 'test_transition';
    const RELATED_CLASS_NAME = 'stdClass';
    const WORKFLOW_NAME = 'test_workflow';
    const FILTER = 'filter != null';

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var WorkflowItemRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var TransitionTriggerCronHelper */
    protected $helper;

    /** @var WorkflowDefinition */
    protected $workflowDefinition;

    /** @var TransitionTriggerCron */
    protected $trigger;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();

        $this->repository = $this->getMockBuilder(WorkflowItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new TransitionTriggerCronHelper($this->workflowManager, $this->repository);

        $this->workflowDefinition = new WorkflowDefinition();
        $this->workflowDefinition->setName(self::WORKFLOW_NAME)->setRelatedEntity(self::RELATED_CLASS_NAME);

        $this->trigger = new TransitionTriggerCron();
        $this->trigger->setWorkflowDefinition($this->workflowDefinition)
            ->setTransitionName(self::TRANSITION_NAME)
            ->setFilter(self::FILTER);
    }

    public function testFetchWorkflowItemsIds()
    {
        $data = [1, 2, 3, 4, 5];
        $steps = ['step1', 'step2'];

        $this->setUpWorkflowManager(self::WORKFLOW_NAME, $steps);

        $this->repository->expects($this->once())
            ->method('getWorkflowItemsIdsByStepsAndEntityClass')
            ->with(new ArrayCollection(array_combine($steps, $steps)), self::RELATED_CLASS_NAME, self::FILTER)
            ->willReturn($data);

        $this->assertEquals($data, $this->helper->fetchWorkflowItemsIdsForTrigger($this->trigger));
    }

    /**
     * @param string $workflowName
     * @param array $steps
     */
    private function setUpWorkflowManager($workflowName, array $steps = [])
    {
        $steps = array_map(
            function ($name) {
                $step = new Step();
                $step->setName($name)->setAllowedTransitions([self::TRANSITION_NAME]);

                return $step;
            },
            $steps
        );

        /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject $workflow */
        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->any())->method('getStepManager')->willReturn(new StepManager($steps));
        $workflow->expects($this->any())->method('getDefinition')->willReturn($this->workflowDefinition);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);
    }
}
