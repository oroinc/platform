<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\PersistIncludedEntities;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Collection\AdditionalEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;

class PersistIncludedEntitiesTest extends BatchUpdateProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private PersistIncludedEntities $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new PersistIncludedEntities($this->doctrineHelper);
    }

    public function testProcessWhenNoBatchItems(): void
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenHasErrors(): void
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('hasErrors')
            ->willReturn(true);
        $itemContext->expects(self::never())
            ->method('getTargetContext');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoTargetContext(): void
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn(null);

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenIncludedEntitiesCollectionDoesNotExist(): void
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn(null);
        $itemTargetContext->expects(self::once())
            ->method('getAdditionalEntityCollection')
            ->willReturn(new AdditionalEntityCollection());

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenIncludedEntitiesCollectionIsEmpty(): void
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn(new IncludedEntityCollection());
        $itemTargetContext->expects(self::once())
            ->method('getAdditionalEntityCollection')
            ->willReturn(new AdditionalEntityCollection());

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessForNewIncludedObject(): void
    {
        $object = new \stdClass();
        $objectClass = 'Test\Class';
        $isExistingObject = false;

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->add(
            $object,
            $objectClass,
            'id',
            new IncludedEntityData('/included/0', 0, $isExistingObject)
        );

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($includedEntities);
        $itemTargetContext->expects(self::once())
            ->method('getAdditionalEntityCollection')
            ->willReturn(new AdditionalEntityCollection());

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($object), false)
            ->willReturn(null);

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessForExistingIncludedObject(): void
    {
        $object = new \stdClass();
        $objectClass = 'Test\Class';
        $isExistingObject = true;

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->add(
            $object,
            $objectClass,
            'id',
            new IncludedEntityData('/included/0', 0, $isExistingObject)
        );

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($includedEntities);
        $itemTargetContext->expects(self::once())
            ->method('getAdditionalEntityCollection')
            ->willReturn(new AdditionalEntityCollection());

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessForNewIncludedEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $isExistingEntity = false;

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->add(
            $entity,
            $entityClass,
            'id',
            new IncludedEntityData('/included/0', 0, $isExistingEntity)
        );

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($includedEntities);
        $itemTargetContext->expects(self::once())
            ->method('getAdditionalEntityCollection')
            ->willReturn(new AdditionalEntityCollection());

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entity));

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWithAdditionalEntitiesToPersist(): void
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $additionalEntityCollection = new AdditionalEntityCollection();
        $additionalEntityCollection->add($entity1);
        $additionalEntityCollection->add($entity2);

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn(null);
        $itemTargetContext->expects(self::once())
            ->method('getAdditionalEntityCollection')
            ->willReturn($additionalEntityCollection);

        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityManager')
            ->withConsecutive(
                [self::identicalTo($entity1), false],
                [self::identicalTo($entity2), false]
            )
            ->willReturn($em);
        $em->expects(self::exactly(2))
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::exactly(2))
            ->method('getEntityState')
            ->withConsecutive(
                [self::identicalTo($entity1)],
                [self::identicalTo($entity2)]
            )
            ->willReturnOnConsecutiveCalls(
                UnitOfWork::STATE_NEW,
                UnitOfWork::STATE_MANAGED
            );
        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entity1));

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWithAdditionalEntitiesToRemove(): void
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $additionalEntityCollection = new AdditionalEntityCollection();
        $additionalEntityCollection->add($entity1, true);
        $additionalEntityCollection->add($entity2, true);

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn(null);
        $itemTargetContext->expects(self::once())
            ->method('getAdditionalEntityCollection')
            ->willReturn($additionalEntityCollection);

        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityManager')
            ->withConsecutive(
                [self::identicalTo($entity1), false],
                [self::identicalTo($entity2), false]
            )
            ->willReturn($em);
        $em->expects(self::exactly(2))
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::exactly(2))
            ->method('getEntityState')
            ->withConsecutive(
                [self::identicalTo($entity1)],
                [self::identicalTo($entity2)]
            )
            ->willReturnOnConsecutiveCalls(
                UnitOfWork::STATE_NEW,
                UnitOfWork::STATE_MANAGED
            );
        $em->expects(self::once())
            ->method('remove')
            ->with(self::identicalTo($entity2));

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }
}
