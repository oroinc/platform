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

class WorkflowStartListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $systemWorkflowManager;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowManager;

    /** @var WorkflowManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowManagerRegistry;

    /** @var WorkflowAwareCache|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowAwareCache;

    /** @var WorkflowStartListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->systemWorkflowManager = $this->createMock(WorkflowManager::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->workflowManagerRegistry = $this->createMock(WorkflowManagerRegistry::class);
        $this->workflowAwareCache = $this->createMock(WorkflowAwareCache::class);

        $this->workflowManagerRegistry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValueMap([
                ['default', $this->workflowManager],
                ['system', $this->systemWorkflowManager],
            ]));

        $this->listener = new WorkflowStartListener($this->workflowManagerRegistry, $this->workflowAwareCache);
    }

    public function testScheduleStartWorkflowForNewEntityNoWorkflow()
    {
        $entity = new \stdClass();

        $this->workflowAwareCache->expects($this->once())
            ->method('hasRelatedActiveWorkflows')
            ->with($entity)->willReturn(false);

        $this->listener->postPersist($this->getEvent($entity));

        $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
    }

    public function testScheduleStartWorkflowForNewEntityNoStartStep()
    {
        $entity = new \stdClass();

        $this->workflowAwareCache->expects($this->once())
            ->method('hasRelatedActiveWorkflows')
            ->with($entity)->willReturn(true);

        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->any())->method('hasStartStep')->willReturn(false);

        $workflow = $this->getWorkflow();
        $workflow->expects($this->any())->method('getStepManager')->willReturn($stepManager);

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([$workflow]);

        $this->systemWorkflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([$workflow]);

        $this->listener->postPersist($this->getEvent($entity));
        $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
    }

    public function testStartWorkflowForNewEntity()
    {
        $entity = new \stdClass();
        $childEntity = new \DateTime();
        $workflowName = 'test_workflow';
        $childWorkflowName = 'test_child_workflow';

        $this->workflowAwareCache->expects($this->at(0))
            ->method('hasRelatedActiveWorkflows')
            ->with($entity)->willReturn(true);
        $this->workflowAwareCache->expects($this->at(1))
            ->method('hasRelatedActiveWorkflows')
            ->with($childEntity)->willReturn(true);

        $this->systemWorkflowManager->expects($this->any())->method('getApplicableWorkflows')->willReturn([]);

        list($event, $workflow) = $this->prepareEventForWorkflow($entity, $workflowName);
        $this->workflowManager->expects($this->at(0))
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([$workflow]);

        list($childEvent, $childWorkflow) = $this->prepareEventForWorkflow($childEntity, $childWorkflowName);
        $this->workflowManager->expects($this->at(1))
            ->method('getApplicableWorkflows')
            ->with($childEntity)
            ->willReturn([$childWorkflow]);

        $this->listener->postPersist($event);

        $expectedSchedule = [
            0 => [
                new WorkflowStartArguments($workflowName, $entity),
            ],
        ];
        $this->assertAttributeEquals(0, 'deepLevel', $this->listener);
        $this->assertAttributeEquals($expectedSchedule, 'entitiesScheduledForWorkflowStart', $this->listener);

        $startChildWorkflow = function () use ($childEvent, $childEntity, $childWorkflow, $childWorkflowName) {
            $this->listener->postPersist($childEvent);

            $expectedSchedule = [
                1 => [
                    new WorkflowStartArguments($childWorkflowName, $childEntity)
                ],
            ];
            $this->assertAttributeEquals(1, 'deepLevel', $this->listener);
            $this->assertAttributeEquals($expectedSchedule, 'entitiesScheduledForWorkflowStart', $this->listener);

            $this->listener->postFlush();

            $this->assertAttributeEquals(1, 'deepLevel', $this->listener);
            $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
        };

        $this->systemWorkflowManager->expects($this->at(0))
            ->method('massStartWorkflow')
            ->with([new WorkflowStartArguments($workflowName, $entity)])
            ->will($this->returnCallback($startChildWorkflow));
        $this->systemWorkflowManager->expects($this->at(1))
            ->method('massStartWorkflow')
            ->with([new WorkflowStartArguments($childWorkflowName, $childEntity)]);

        $this->listener->postFlush();

        $this->assertAttributeEquals(0, 'deepLevel', $this->listener);
        $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
    }

    /**
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
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
     * @return LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject
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
