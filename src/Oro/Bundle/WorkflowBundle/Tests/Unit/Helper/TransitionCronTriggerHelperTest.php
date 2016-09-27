<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\TransitionCronTriggerHelper;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionCronTriggerHelperTest extends \PHPUnit_Framework_TestCase
{
    const TRANSITION_NAME = 'test_transition';
    const RELATED_CLASS_NAME = 'stdClass';
    const RELATED_CLASS_ID_FIELD = 'id';
    const WORKFLOW_NAME = 'test_workflow';
    const FILTER = 'filter != null';

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var WorkflowItemRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var TransitionCronTriggerHelper */
    protected $helper;

    /** @var WorkflowDefinition */
    protected $workflowDefinition;

    /** @var TransitionCronTrigger */
    protected $trigger;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();

        $this->repository = $this->getMockBuilder(WorkflowItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = new ClassMetadataInfo(self::RELATED_CLASS_NAME);
        $metadata->setIdentifier([self::RELATED_CLASS_ID_FIELD]);

        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->any())->method('getClassMetadata')->with(self::RELATED_CLASS_NAME)->willReturn($metadata);

        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::RELATED_CLASS_NAME)
            ->willReturn($em);

        $this->helper = new TransitionCronTriggerHelper($this->workflowManager, $this->repository, $this->registry);

        $this->workflowDefinition = new WorkflowDefinition();
        $this->workflowDefinition->setName(self::WORKFLOW_NAME)->setRelatedEntity(self::RELATED_CLASS_NAME);

        $this->trigger = new TransitionCronTrigger();
        $this->trigger->setWorkflowDefinition($this->workflowDefinition)
            ->setTransitionName(self::TRANSITION_NAME)
            ->setFilter(self::FILTER);
    }

    public function testFetchWorkflowItems()
    {
        $data = [1, 2, 3, 4, 5];
        $steps = ['step1', 'step2'];

        $this->setUpWorkflowManager(self::WORKFLOW_NAME, $steps);

        $this->repository->expects($this->once())
            ->method('findByStepNamesAndEntityClass')
            ->with(
                new ArrayCollection(array_combine($steps, $steps)),
                self::RELATED_CLASS_NAME,
                self::RELATED_CLASS_ID_FIELD,
                self::FILTER
            )
            ->willReturn($data);

        $this->assertEquals($data, $this->helper->fetchWorkflowItemsForTrigger($this->trigger));
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
