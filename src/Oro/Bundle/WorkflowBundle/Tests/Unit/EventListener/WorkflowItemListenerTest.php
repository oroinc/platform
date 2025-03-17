<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemListener;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowItemListenerTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private WorkflowManager&MockObject $systemWorkflowManager;
    private WorkflowEntityConnector&MockObject $entityConnector;
    private WorkflowAwareCache&MockObject $workflowAwareCache;
    private WorkflowItemListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($object) {
                return get_class($object);
            });

        $this->systemWorkflowManager = $this->createMock(WorkflowManager::class);

        $workflowManagerRegistry = $this->createMock(WorkflowManagerRegistry::class);
        $workflowManagerRegistry->expects(self::any())
            ->method('getManager')
            ->with('system')
            ->willReturn($this->systemWorkflowManager);

        $this->entityConnector = $this->createMock(WorkflowEntityConnector::class);
        $this->workflowAwareCache = $this->createMock(WorkflowAwareCache::class);

        $this->listener = new WorkflowItemListener(
            $this->doctrineHelper,
            $workflowManagerRegistry,
            $this->entityConnector,
            $this->workflowAwareCache
        );
    }

    public function testPostPersistScheduleExtraUpdate(): void
    {
        $entity = new \stdClass();
        $entityId = 1;
        $entityClass = 'stdClass';

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);
        $workflowItem->expects(self::once())
            ->method('setEntityId')
            ->with($entityId);
        $workflowItem->expects(self::once())
            ->method('setEntityClass')
            ->with($entityClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects(self::once())
            ->method('scheduleExtraUpdate')
            ->with($workflowItem, ['entityId' => [null, $entityId], 'entityClass' => [null, $entityClass]]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->listener->postPersist($workflowItem, $this->getEvent($workflowItem, $em));
    }

    public function testUpdateWorkflowItemEntityRelationException(): void
    {
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Workflow item does not contain related entity');

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getEntity');

        $this->listener->postPersist($workflowItem, $this->getEvent($workflowItem));
    }

    public function testUpdateWorkflowItemNoEntityRelationIdException(): void
    {
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage(
            'Workflow "test_workflow" can not be started because ID of related entity is null'
        );

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn(new \stdClass());

        $this->listener->postPersist($workflowItem, $this->getEvent($workflowItem));
    }

    /**
     * @dataProvider preRemoveDataProvider
     */
    public function testPreRemove(bool $hasWorkflowItems = false): void
    {
        $entity = new \stdClass();
        $workflowItem = new WorkflowItem();

        $this->entityConnector->expects(self::once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(true);

        $this->workflowAwareCache->expects(self::once())
            ->method('hasRelatedWorkflows')
            ->with($entity)
            ->willReturn(true);

        $this->systemWorkflowManager->expects(self::once())
            ->method('getWorkflowItemsByEntity')
            ->with($entity)
            ->willReturn($hasWorkflowItems ? [$workflowItem] : []);

        $entityManager = null;
        if ($hasWorkflowItems) {
            $entityManager = $this->createMock(EntityManagerInterface::class);
            $entityManager->expects(self::once())
                ->method('remove')
                ->with($workflowItem);
        }

        $this->listener->preRemove($this->getEvent($entity, $entityManager));
    }

    public function preRemoveDataProvider(): array
    {
        return [
            'aware entity without workflow item' => [],
            'aware entity with workflow item' => [
                'hasWorkflowItem' => true,
            ],
        ];
    }

    public function testPreRemoveWithUnsupportedEntity(): void
    {
        $entity = new \DateTime();

        $this->entityConnector->expects(self::once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(false);

        $this->workflowAwareCache->expects(self::never())
            ->method(self::anything());

        $this->listener->preRemove($this->getEvent($entity));
    }

    public function testPreRemoveWithEntityNotInWorkflow(): void
    {
        $entity = new \stdClass();

        $this->entityConnector->expects(self::once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(true);

        $this->workflowAwareCache->expects(self::once())
            ->method('hasRelatedWorkflows')
            ->willReturn(false);

        $this->systemWorkflowManager->expects(self::never())
            ->method(self::anything());

        $this->listener->preRemove($this->getEvent($entity));
    }

    private function getEvent(object $entity, ?EntityManagerInterface $entityManager = null): LifecycleEventArgs
    {
        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects(self::any())
            ->method('getObject')
            ->willReturn($entity);
        $event->expects(self::exactly($entityManager ? 1 : 0))
            ->method('getObjectManager')
            ->willReturn($entityManager);

        return $event;
    }
}
