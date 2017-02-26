<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemListener;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowItemListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowDefinitionRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $systemWorkflowManager;

    /** @var WorkflowManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManagerRegistry;

    /** @var WorkflowEntityConnector|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityConnector;

    /** @var WorkflowItemListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->repository = $this->createMock(WorkflowDefinitionRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->repository);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(
                function ($object) {
                    return get_class($object);
                }
            );

        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->systemWorkflowManager = $this->createMock(WorkflowManager::class);
        $this->workflowManagerRegistry = $this->createMock(WorkflowManagerRegistry::class);
        $this->entityConnector = $this->createMock(WorkflowEntityConnector::class);

        $this->workflowManagerRegistry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValueMap([
                [null, $this->workflowManager],
                ['system', $this->systemWorkflowManager],
            ]));

        $this->listener = new WorkflowItemListener(
            $this->doctrineHelper,
            $this->workflowManagerRegistry,
            $this->entityConnector
        );
    }

    public function testUpdateWorkflowItemEntityRelation()
    {
        $entity = new \stdClass();
        $entityId = 1;
        $entityClass = 'stdClass';

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);
        $workflowItem->expects($this->once())
            ->method('setEntityId')
            ->with($entityId);
        $workflowItem->expects($this->once())
            ->method('setEntityClass')
            ->with($entityClass);

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with($workflowItem, ['entityId' => [null, $entityId], 'entityClass' => [null, $entityClass]]);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $workflow = $this->getWorkflow();
        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with($workflowItem)
            ->willReturn([$workflow]);
        $this->systemWorkflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with($workflowItem)
            ->willReturn([$workflow]);

        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->any())->method('hasStartStep')
            ->will($this->returnValue(false));

        $workflow->expects($this->any())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));

        $this->listener->postPersist($this->getEvent($workflowItem, $em));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow item does not contain related entity
     */
    public function testUpdateWorkflowItemEntityRelationException()
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getEntity');

        $this->listener->postPersist($this->getEvent($workflowItem));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow "test_workflow" can not be started because ID of related entity is null
     */
    public function testUpdateWorkflowItemNoEntityRelationIdException()
    {
        $workflowItem = $this->getMockBuilder(WorkflowItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())->method('getWorkflowName')->willReturn('test_workflow');
        $workflowItem->expects($this->once())->method('getEntity')->willReturn(new \stdClass());

        $this->listener->postPersist($this->getEvent($workflowItem));
    }

    /**
     * @param bool $hasWorkflowItems
     * @dataProvider preRemoveDataProvider
     */
    public function testPreRemove($hasWorkflowItems = false)
    {
        $entity = new \stdClass();
        $workflowItem = new WorkflowItem();

        $this->entityConnector->expects($this->once())->method('isApplicableEntity')->with($entity)->willReturn(true);

        $this->repository->expects($this->once())->method('getAllRelatedEntityClasses')->willReturn(['stdClass']);

        $this->systemWorkflowManager->expects($this->once())
            ->method('getWorkflowItemsByEntity')
            ->with($entity)
            ->willReturn($hasWorkflowItems ? [$workflowItem] : null);
        $entityManager = null;
        if ($hasWorkflowItems) {
            $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();
            $entityManager->expects($this->once())
                ->method('remove')
                ->with($workflowItem);
        }

        $this->listener->preRemove($this->getEvent($entity, $entityManager));
    }

    /**
     * @return array
     */
    public function preRemoveDataProvider()
    {
        return [
            'aware entity without workflow item' => [],
            'aware entity with workflow item' => [
                'hasWorkflowItem' => true,
            ],
        ];
    }

    public function testPreRemoveWithUnsupportedEntity()
    {
        $entity = new \DateTime();

        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(false);

        $this->workflowManager->expects($this->never())->method($this->anything());

        $this->listener->preRemove($this->getEvent($entity));
    }

    public function testPreRemoveWithEntityNotInWorkflow()
    {
        $entity = new \stdClass();

        $this->entityConnector->expects($this->once())->method('isApplicableEntity')->with($entity)->willReturn(true);

        $this->repository->expects($this->once())->method('getAllRelatedEntityClasses')->willReturn([]);

        $this->systemWorkflowManager->expects($this->never())->method($this->anything());

        $this->listener->preRemove($this->getEvent($entity));
    }

    public function testClearLocalCacheOnPpostFlush()
    {
        $entity = new \stdClass();
        $event = $this->getEvent($entity);

        $this->entityConnector->expects($this->exactly(5))
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(true);
        $this->systemWorkflowManager->expects($this->exactly(5))
            ->method('getWorkflowItemsByEntity')
            ->with($entity);

        $this->repository->expects($this->exactly(2))->method('getAllRelatedEntityClasses')->willReturn(['stdClass']);

        //local cache should be created
        $this->listener->preRemove($event);

        //should be used local cache
        $this->listener->preRemove($event);
        $this->listener->preRemove($event);
        $this->listener->preRemove($event);

        //local cache should be cleared
        $this->listener->postFlush();

        $this->listener->preRemove($event);
    }

    public function testScheduleStartWorkflowForNewEntityNoWorkflow()
    {
        $entity = new \stdClass();

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([]);
        $this->systemWorkflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([]);

        $this->listener->postPersist($this->getEvent($entity));

        $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
    }

    public function testScheduleStartWorkflowForNewEntityNoStartStep()
    {
        $entity = new \stdClass();

        $stepManager = $this->createMock('Oro\Bundle\WorkflowBundle\Model\StepManager');
        $stepManager->expects($this->any())->method('hasStartStep')
            ->will($this->returnValue(false));

        $workflow = $this->getWorkflow();
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));

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

        $expectedSchedule = array(
            0 => array(
                new WorkflowStartArguments($workflowName, $entity),
            ),
        );
        $this->assertAttributeEquals(0, 'deepLevel', $this->listener);
        $this->assertAttributeEquals($expectedSchedule, 'entitiesScheduledForWorkflowStart', $this->listener);

        $startChildWorkflow = function () use ($childEvent, $childEntity, $childWorkflow, $childWorkflowName) {
            $this->listener->postPersist($childEvent);

            $expectedSchedule = array(
                1 => array(
                    new WorkflowStartArguments($childWorkflowName, $childEntity)
                ),
            );
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
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWorkflow()
    {
        $workflow = $this->createMock(Workflow::class);
        $definition = new WorkflowDefinition();
        $definition->setConfiguration(['start_type' => 'default']);
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

        $stepManager = $this->createMock('Oro\Bundle\WorkflowBundle\Model\StepManager');
        $stepManager->expects($this->any())->method('hasStartStep')
            ->will($this->returnValue(true));

        $workflow = $this->getWorkflow();
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));
        $workflow->expects($this->any())->method('getName')->willReturn($workflowName);

        return array($event, $workflow);
    }

    /**
     * @param $entity
     * @param EntityManager|null $entityManager
     * @return LifecycleEventArgs
     */
    private function getEvent($entity, EntityManager $entityManager = null)
    {
        $event = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->atLeastOnce())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $event->expects($this->exactly($entityManager ? 1 : 0))
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        return $event;
    }
}
