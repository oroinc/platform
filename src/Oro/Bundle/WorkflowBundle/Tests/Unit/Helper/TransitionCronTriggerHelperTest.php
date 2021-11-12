<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Helper\TransitionCronTriggerHelper;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowAwareEntityFetcher;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class TransitionCronTriggerHelperTest extends \PHPUnit\Framework\TestCase
{
    private const TRANSITION_NAME = 'test_transition';
    private const RELATED_CLASS_NAME = 'stdClass';
    private const RELATED_CLASS_ID_FIELD = 'id';
    private const FILTER = 'filter != null';

    /** @var WorkflowItemRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var WorkflowAwareEntityFetcher|\PHPUnit\Framework\MockObject\MockObject */
    private $fetcher;

    /** @var TransitionCronTriggerHelper */
    private $helper;

    /** @var TransitionCronTrigger */
    private $trigger;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WorkflowItemRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(WorkflowItem::class)
            ->willReturn($this->repository);

        $this->fetcher = $this->createMock(WorkflowAwareEntityFetcher::class);

        $this->helper = new TransitionCronTriggerHelper($this->doctrineHelper, $this->fetcher);

        $this->trigger = new TransitionCronTrigger();
        $this->trigger->setTransitionName(self::TRANSITION_NAME)->setFilter(self::FILTER);
    }

    public function testFetchEntitiesWithoutWorkflowItems()
    {
        $workflow = $this->getWorkflow();
        $expected = [new \stdClass(), new \stdClass()];

        $this->fetcher->expects($this->any())
            ->method('getEntitiesWithoutWorkflowItem')
            ->with($workflow->getDefinition(), self::FILTER)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->helper->fetchEntitiesWithoutWorkflowItems($this->trigger, $workflow));
    }

    public function testFetchWorkflowItems()
    {
        $data = [1, 2, 3, 4, 5];
        $steps = ['step1', 'step2'];

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::RELATED_CLASS_NAME)
            ->willReturn(self::RELATED_CLASS_ID_FIELD);

        $this->repository->expects($this->once())
            ->method('findByStepNamesAndEntityClass')
            ->with(
                new ArrayCollection(array_combine($steps, $steps)),
                self::RELATED_CLASS_NAME,
                self::RELATED_CLASS_ID_FIELD,
                self::FILTER
            )
            ->willReturn($data);

        $this->assertEquals(
            $data,
            $this->helper->fetchWorkflowItemsForTrigger($this->trigger, $this->getWorkflow($steps))
        );
    }

    private function getWorkflow(array $steps = []): Workflow
    {
        $steps = array_map(
            function ($name) {
                $step = new Step();
                $step->setName($name)->setAllowedTransitions([self::TRANSITION_NAME]);

                return $step;
            },
            $steps
        );

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setRelatedEntity(self::RELATED_CLASS_NAME);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->willReturn(new StepManager($steps));
        $workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        return $workflow;
    }
}
