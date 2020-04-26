<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowStartListener;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;
use PHPUnit\Framework\MockObject\MockObject;

class WorkflowStartListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|MockObject */
    protected $systemWorkflowManager;

    /** @var WorkflowManager|MockObject */
    protected $workflowManager;

    /** @var WorkflowManagerRegistry|MockObject */
    protected $workflowManagerRegistry;

    /** @var WorkflowAwareCache|MockObject */
    protected $workflowAwareCache;

    /** @var WorkflowStartListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->systemWorkflowManager = $this->createMock(WorkflowManager::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->workflowManagerRegistry = $this->createMock(WorkflowManagerRegistry::class);
        $this->workflowAwareCache = $this->createMock(WorkflowAwareCache::class);

        $this->workflowManagerRegistry->expects(static::any())
            ->method('getManager')
            ->willReturnMap([
                ['default', $this->workflowManager],
                ['system', $this->systemWorkflowManager],
            ]);

        $this->listener = new class(
            $this->workflowManagerRegistry,
            $this->workflowAwareCache
        ) extends WorkflowStartListener {
            public function xgetEntitiesScheduledForWorkflowStart(): array
            {
                return $this->entitiesScheduledForWorkflowStart;
            }

            public function xgetDeepLevel(): int
            {
                return $this->deepLevel;
            }
        };
    }

    public function testScheduleStartWorkflowForNewEntityNoWorkflow()
    {
        $entity = new \stdClass();

        $this->workflowAwareCache->expects(static::once())
            ->method('hasRelatedActiveWorkflows')
            ->with($entity)->willReturn(false);

        $this->listener->postPersist($this->getEvent($entity));

        static::assertEmpty($this->listener->xgetEntitiesScheduledForWorkflowStart());
    }

    public function testScheduleStartWorkflowForNewEntityNoStartStep()
    {
        $entity = new \stdClass();

        $this->workflowAwareCache->expects(static::once())
            ->method('hasRelatedActiveWorkflows')
            ->with($entity)
            ->willReturn(true);

        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects(static::any())->method('hasStartStep')->willReturn(false);

        $workflow = $this->getWorkflow();
        $workflow->expects(static::any())->method('getStepManager')->willReturn($stepManager);

        $this->workflowManager->expects(static::once())
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([$workflow]);

        $this->systemWorkflowManager->expects(static::once())
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([$workflow]);

        $this->listener->postPersist($this->getEvent($entity));

        static::assertEmpty($this->listener->xgetEntitiesScheduledForWorkflowStart());
    }

    public function testStartWorkflowForNewEntity()
    {
        $entity = new \stdClass();
        $childEntity = new \DateTime();
        $workflowName = 'test_workflow';
        $childWorkflowName = 'test_child_workflow';

        $this->workflowAwareCache->expects(static::at(0))
            ->method('hasRelatedActiveWorkflows')
            ->with($entity)
            ->willReturn(true);
        $this->workflowAwareCache->expects(static::at(1))
            ->method('hasRelatedActiveWorkflows')
            ->with($childEntity)
            ->willReturn(true);

        $this->systemWorkflowManager->expects(static::any())->method('getApplicableWorkflows')->willReturn([]);

        list($event, $workflow) = $this->prepareEventForWorkflow($entity, $workflowName);
        $this->workflowManager->expects(static::at(0))
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([$workflow]);

        list($childEvent, $childWorkflow) = $this->prepareEventForWorkflow($childEntity, $childWorkflowName);
        $this->workflowManager->expects(static::at(1))
            ->method('getApplicableWorkflows')
            ->with($childEntity)
            ->willReturn([$childWorkflow]);

        $this->listener->postPersist($event);

        $expectedSchedule = [
            0 => [
                new WorkflowStartArguments($workflowName, $entity),
            ],
        ];
        static::assertEquals(0, $this->listener->xgetDeepLevel());
        static::assertEquals($expectedSchedule, $this->listener->xgetEntitiesScheduledForWorkflowStart());

        $startChildWorkflow = function () use ($childEvent, $childEntity, $childWorkflow, $childWorkflowName) {
            $this->listener->postPersist($childEvent);

            $expectedSchedule = [
                1 => [
                    new WorkflowStartArguments($childWorkflowName, $childEntity)
                ],
            ];
            static::assertEquals(1, $this->listener->xgetDeepLevel());
            static::assertEquals($expectedSchedule, $this->listener->xgetEntitiesScheduledForWorkflowStart());

            $this->listener->postFlush();

            static::assertEquals(1, $this->listener->xgetDeepLevel());
            static::assertEmpty($this->listener->xgetEntitiesScheduledForWorkflowStart());
        };

        $this->systemWorkflowManager->expects(static::at(0))
            ->method('massStartWorkflow')
            ->with([new WorkflowStartArguments($workflowName, $entity)])
            ->willReturnCallback($startChildWorkflow);
        $this->systemWorkflowManager->expects(static::at(1))
            ->method('massStartWorkflow')
            ->with([new WorkflowStartArguments($childWorkflowName, $childEntity)]);

        $this->listener->postFlush();

        static::assertEquals(0, $this->listener->xgetDeepLevel());
        static::assertEmpty($this->listener->xgetEntitiesScheduledForWorkflowStart());
    }

    /**
     * @return Workflow|MockObject
     */
    protected function getWorkflow()
    {
        $definition = new WorkflowDefinition();
        $definition->setConfiguration(['start_type' => 'default']);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())->method('getDefinition')->willReturn($definition);

        return $workflow;
    }

    /**
     * @param object $entity
     * @param string $workflowName
     * @return array
     */
    protected function prepareEventForWorkflow($entity, $workflowName)
    {
        $event = $this->getEvent($entity);

        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->any())->method('hasStartStep')->willReturn(true);

        $workflow = $this->getWorkflow();
        $workflow->expects($this->any())->method('getStepManager')->willReturn($stepManager);
        $workflow->expects($this->any())->method('getName')->willReturn($workflowName);

        return [$event, $workflow];
    }

    /**
     * @param $entity
     * @param EntityManager|null $entityManager
     * @return LifecycleEventArgs|MockObject
     */
    private function getEvent($entity, EntityManager $entityManager = null)
    {
        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->atLeastOnce())->method('getEntity')->will($this->returnValue($entity));
        $event->expects($this->exactly($entityManager ? 1 : 0))
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        return $event;
    }
}
