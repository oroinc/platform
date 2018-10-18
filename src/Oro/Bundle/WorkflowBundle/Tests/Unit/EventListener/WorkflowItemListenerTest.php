<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemListener;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;

class WorkflowItemListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $systemWorkflowManager;

    /** @var WorkflowManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowManagerRegistry;

    /** @var WorkflowEntityConnector|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConnector;

    /** @var WorkflowAwareCache|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowAwareCache;

    /** @var WorkflowItemListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(
                function ($object) {
                    return get_class($object);
                }
            );

        $this->systemWorkflowManager = $this->createMock(WorkflowManager::class);

        $this->workflowManagerRegistry = $this->createMock(WorkflowManagerRegistry::class);
        $this->workflowManagerRegistry->expects($this->any())
            ->method('getManager')
            ->with('system')->willReturn($this->systemWorkflowManager);

        $this->entityConnector = $this->createMock(WorkflowEntityConnector::class);
        $this->workflowAwareCache = $this->createMock(WorkflowAwareCache::class);

        $this->listener = new WorkflowItemListener(
            $this->doctrineHelper,
            $this->workflowManagerRegistry,
            $this->entityConnector,
            $this->workflowAwareCache
        );
    }

    public function testPostPersistScheduleExtraUpdate()
    {
        $entity = new \stdClass();
        $entityId = 1;
        $entityClass = 'stdClass';

        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())->method('getEntity')->willReturn($entity);
        $workflowItem->expects($this->once())->method('setEntityId')->with($entityId);
        $workflowItem->expects($this->once())->method('setEntityClass')->with($entityClass);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with($workflowItem, ['entityId' => [null, $entityId], 'entityClass' => [null, $entityClass]]);

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())->method('getUnitOfWork')->willReturn($uow);

        $this->listener->postPersist($workflowItem, $this->getEvent($workflowItem, $em));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow item does not contain related entity
     */
    public function testUpdateWorkflowItemEntityRelationException()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())->method('getEntity');

        $this->listener->postPersist($workflowItem, $this->getEvent($workflowItem));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow "test_workflow" can not be started because ID of related entity is null
     */
    public function testUpdateWorkflowItemNoEntityRelationIdException()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())->method('getWorkflowName')->willReturn('test_workflow');
        $workflowItem->expects($this->once())->method('getEntity')->willReturn(new \stdClass());

        $this->listener->postPersist($workflowItem, $this->getEvent($workflowItem));
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

        $this->workflowAwareCache->expects($this->once())
            ->method('hasRelatedWorkflows')
            ->with($entity)
            ->willReturn(true);

        $this->systemWorkflowManager->expects($this->once())
            ->method('getWorkflowItemsByEntity')
            ->with($entity)
            ->willReturn($hasWorkflowItems ? [$workflowItem] : []);

        $entityManager = null;
        if ($hasWorkflowItems) {
            $entityManager = $this->createMock('Doctrine\ORM\EntityManager');
            $entityManager->expects($this->once())->method('remove')->with($workflowItem);
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

        $this->entityConnector->expects($this->once())->method('isApplicableEntity')->with($entity)->willReturn(false);

        $this->workflowAwareCache->expects($this->never())->method($this->anything());

        $this->listener->preRemove($this->getEvent($entity));
    }

    public function testPreRemoveWithEntityNotInWorkflow()
    {
        $entity = new \stdClass();

        $this->entityConnector->expects($this->once())->method('isApplicableEntity')->with($entity)->willReturn(true);

        $this->workflowAwareCache->expects($this->once())->method('hasRelatedWorkflows')->willReturn(false);

        $this->systemWorkflowManager->expects($this->never())->method($this->anything());

        $this->listener->preRemove($this->getEvent($entity));
    }

    /**
     * @param object $entity
     * @param EntityManager|null $entityManager
     * @return LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEvent($entity, EntityManager $entityManager = null)
    {
        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getEntity')->willReturn($entity);
        $event->expects($this->exactly($entityManager ? 1 : 0))->method('getEntityManager')->willReturn($entityManager);

        return $event;
    }
}
