<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;

class WorkflowDefinitionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository */
    protected $entityRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var WorkflowAssembler|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowAssembler;

    /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflow;

    /** @var StepManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $stepManager;

    /** @var WorkflowDefinitionHandler */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->workflowAssembler = $this->getMockBuilder(WorkflowAssembler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stepManager = $this->getMockBuilder(StepManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->entityRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getMock(ManagerRegistry::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);

        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->handler = new WorkflowDefinitionHandler(
            $this->workflowAssembler,
            $this->eventDispatcher,
            $managerRegistry,
            'OroWorkflowBundle:WorkflowDefinition'
        );
    }

    public function testCreateWorkflowDefinition()
    {
        $newDefinition = new WorkflowDefinition();

        $this->workflowAssembler->expects($this->once())->method('assemble');

        $this->entityManager->expects($this->once())->method('persist')->with($newDefinition);
        $this->entityManager->expects($this->once())->method('flush')->with();

        $changes = new WorkflowChangesEvent($newDefinition);

        $beforeEvent = WorkflowEvents::WORKFLOW_BEFORE_CREATE;
        $afterEvent = WorkflowEvents::WORKFLOW_AFTER_CREATE;

        $this->eventDispatcher->expects($this->at(0))->method('dispatch')->with($beforeEvent, $changes);
        $this->eventDispatcher->expects($this->at(1))->method('dispatch')->with($afterEvent, $changes);

        $this->handler->createWorkflowDefinition($newDefinition);
    }

    public function testUpdateWorkflowDefinition()
    {
        $existingDefinition = (new WorkflowDefinition())->setName('existing');
        $newDefinition = (new WorkflowDefinition())->setName('updated');

        $this->workflowAssembler->expects($this->once())->method('assemble');
//        $this->workflowAssembler->expects($this->once())->method('assemble')->willReturn($this->workflow);
//        $this->workflow->expects($this->once())->method('getStepManager')->willReturn($this->stepManager);
//        $this->stepManager->expects($this->once())->method('getSteps')->willReturn($this->getSteps());

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $changes = new WorkflowChangesEvent($existingDefinition, (new WorkflowDefinition())->setName('existing'));

        $beforeEvent = WorkflowEvents::WORKFLOW_BEFORE_UPDATE;
        $afterEvent = WorkflowEvents::WORKFLOW_AFTER_UPDATE;

        $this->eventDispatcher->expects($this->at(0))->method('dispatch')->with($beforeEvent, $changes);
        $this->eventDispatcher->expects($this->at(1))->method('dispatch')->with($afterEvent, $changes);

        $this->handler->updateWorkflowDefinition($existingDefinition, $newDefinition);

        //$this->assertEquals($this->getWorkflowSteps($existingDefinition), $existingDefinition->getSteps());
    }

    /**
     * @dataProvider deleteWorkflowDefinitionDataProvider
     *
     * @param WorkflowDefinition $definition
     * @param bool $expected
     */
    public function testDeleteWorkflowDefinition(WorkflowDefinition $definition, $expected)
    {
        $this->entityManager
            ->expects($this->exactly((int)$expected))
            ->method('remove');

        $this->entityManager
            ->expects($this->exactly((int)$expected))
            ->method('flush');

        $this->eventDispatcher
            ->expects($this->exactly((int)$expected))
            ->method('dispatch')
            ->with(WorkflowEvents::WORKFLOW_AFTER_DELETE, $this->equalTo(new WorkflowChangesEvent($definition)));

        $this->assertEquals($expected, $this->handler->deleteWorkflowDefinition($definition));
    }

    /**
     * @return array
     */
    public function deleteWorkflowDefinitionDataProvider()
    {
        $definition1 = new WorkflowDefinition();
        $definition1
            ->setName('definition1')
            ->setLabel('label1');

        $definition2 = new WorkflowDefinition();
        $definition2
            ->setName('definition2')
            ->setLabel('label2')
            ->setSystem(true);

        return [
            'with new definition' => [
                'definition' => $definition1,
                'expected' => true,
            ],
            'with existing definition' => [
                'definition' => $definition2,
                'expected' => false,
            ],
        ];
    }

    /**
     * @return Step[]
     */
    protected function getSteps()
    {
        return [
            (new Step())
                ->setName('step1')
                ->setLabel('Step1 Label')
                ->setOrder(10)
                ->setFinal(false)
        ];
    }

    /**
     * @param WorkflowDefinition $definition
     * @return WorkflowStep[]|ArrayCollection
     */
    protected function getWorkflowSteps(WorkflowDefinition $definition)
    {
        $steps = new ArrayCollection();

        foreach ($this->getSteps() as $step) {
            $workflowStep = new WorkflowStep();
            $workflowStep
                ->setName($step->getName())
                ->setLabel($step->getLabel())
                ->setStepOrder($step->getOrder())
                ->setFinal($step->isFinal())
                ->setDefinition($definition);

            $steps->add($workflowStep);
        }

        return $steps;
    }
}
