<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowStartListener;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class WorkflowStartListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $systemWorkflowManager;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var WorkflowManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManagerRegistry;

    /** @var WorkflowAwareCache|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowAwareCache;

    /** @var WorkflowStartListener */
    private $listener;

    protected function setUp(): void
    {
        $this->systemWorkflowManager = $this->createMock(WorkflowManager::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->workflowManagerRegistry = $this->createMock(WorkflowManagerRegistry::class);
        $this->workflowAwareCache = $this->createMock(WorkflowAwareCache::class);

        $this->workflowManagerRegistry->expects(self::any())
            ->method('getManager')
            ->willReturnMap([
                ['default', $this->workflowManager],
                ['system', $this->systemWorkflowManager],
            ]);

        $this->listener = new WorkflowStartListener(
            $this->workflowManagerRegistry,
            $this->workflowAwareCache
        );
    }

    public function testScheduleStartWorkflowForNewEntityNoWorkflow()
    {
        $entity = new \stdClass();

        $this->workflowAwareCache->expects(self::once())
            ->method('hasRelatedActiveWorkflows')
            ->with($entity)
            ->willReturn(false);

        $this->listener->postPersist($this->getEvent($entity));

        self::assertEmpty($this->getEntitiesScheduledForWorkflowStart($this->listener));
    }

    public function testScheduleStartWorkflowForNewEntityNoStartStep()
    {
        $entity = new \stdClass();

        $this->workflowAwareCache->expects(self::once())
            ->method('hasRelatedActiveWorkflows')
            ->with($entity)
            ->willReturn(true);

        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects(self::any())
            ->method('hasStartStep')
            ->willReturn(false);

        $workflow = $this->getWorkflow();
        $workflow->expects(self::any())
            ->method('getStepManager')
            ->willReturn($stepManager);

        $this->workflowManager->expects(self::once())
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([$workflow]);

        $this->systemWorkflowManager->expects(self::once())
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([$workflow]);

        $this->listener->postPersist($this->getEvent($entity));

        self::assertEmpty($this->getEntitiesScheduledForWorkflowStart($this->listener));
    }

    public function testStartWorkflowForNewEntity()
    {
        $entity = new \stdClass();
        $childEntity = new \DateTime();
        $workflowName = 'test_workflow';
        $childWorkflowName = 'test_child_workflow';

        $this->workflowAwareCache->expects(self::exactly(2))
            ->method('hasRelatedActiveWorkflows')
            ->withConsecutive(
                [$entity],
                [$childEntity]
            )
            ->willReturn(true);

        $this->systemWorkflowManager->expects(self::any())
            ->method('getApplicableWorkflows')
            ->willReturn([]);

        [$event, $workflow] = $this->prepareEventForWorkflow($entity, $workflowName);
        [$childEvent, $childWorkflow] = $this->prepareEventForWorkflow($childEntity, $childWorkflowName);
        $this->workflowManager->expects(self::exactly(2))
            ->method('getApplicableWorkflows')
            ->withConsecutive(
                [$entity],
                [$childEntity]
            )
            ->willReturnOnConsecutiveCalls(
                [$workflow],
                [$childWorkflow]
            );

        $this->listener->postPersist($event);

        $expectedSchedule = [
            0 => [
                new WorkflowStartArguments($workflowName, $entity),
            ],
        ];
        self::assertEquals(0, $this->getDeepLevel($this->listener));
        self::assertEquals($expectedSchedule, $this->getEntitiesScheduledForWorkflowStart($this->listener));

        $startChildWorkflow = function () use ($childEvent, $childEntity, $childWorkflowName) {
            $this->listener->postPersist($childEvent);

            $expectedSchedule = [
                1 => [
                    new WorkflowStartArguments($childWorkflowName, $childEntity)
                ],
            ];
            self::assertEquals(1, $this->getDeepLevel($this->listener));
            self::assertEquals($expectedSchedule, $this->getEntitiesScheduledForWorkflowStart($this->listener));

            $this->listener->postFlush();

            self::assertEquals(1, $this->getDeepLevel($this->listener));
            self::assertEmpty($this->getEntitiesScheduledForWorkflowStart($this->listener));
        };

        $this->systemWorkflowManager->expects(self::exactly(2))
            ->method('massStartWorkflow')
            ->withConsecutive(
                [[new WorkflowStartArguments($workflowName, $entity)]],
                [[new WorkflowStartArguments($childWorkflowName, $childEntity)]]
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback($startChildWorkflow),
                new ReturnCallback(function () {
                })
            );

        $this->listener->postFlush();

        self::assertEquals(0, $this->getDeepLevel($this->listener));
        self::assertEmpty($this->getEntitiesScheduledForWorkflowStart($this->listener));
    }

    /**
     * @return mixed
     */
    private function getDeepLevel(WorkflowStartListener $listener)
    {
        return ReflectionUtil::getPropertyValue($listener, 'deepLevel');
    }

    /**
     * @return mixed
     */
    private function getEntitiesScheduledForWorkflowStart(WorkflowStartListener $listener)
    {
        return ReflectionUtil::getPropertyValue($listener, 'entitiesScheduledForWorkflowStart');
    }

    /**
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getWorkflow()
    {
        $definition = new WorkflowDefinition();
        $definition->setConfiguration(['start_type' => 'default']);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $workflow;
    }

    private function prepareEventForWorkflow(object $entity, string $workflowName): array
    {
        $event = $this->getEvent($entity);

        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->any())
            ->method('hasStartStep')
            ->willReturn(true);

        $workflow = $this->getWorkflow();
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->willReturn($stepManager);
        $workflow->expects($this->any())
            ->method('getName')
            ->willReturn($workflowName);

        return [$event, $workflow];
    }

    /**
     * @return LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEvent(object $entity)
    {
        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->atLeastOnce())
            ->method('getEntity')
            ->willReturn($entity);

        return $event;
    }
}
