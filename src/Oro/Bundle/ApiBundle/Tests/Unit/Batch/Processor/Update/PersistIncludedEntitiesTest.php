<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\PersistIncludedEntities;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class PersistIncludedEntitiesTest extends BatchUpdateProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var PersistIncludedEntities */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new PersistIncludedEntities($this->doctrineHelper);
    }

    public function testProcessWhenNoBatchItems()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenHasErrors()
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

    public function testProcessWhenNoTargetContext()
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

    public function testProcessWhenIncludedDataDoesNotExist()
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
            ->method('getIncludedData')
            ->willReturn(null);
        $itemTargetContext->expects(self::never())
            ->method('getIncludedEntities');

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenIncludedDataIsEmpty()
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
            ->method('getIncludedData')
            ->willReturn([]);
        $itemTargetContext->expects(self::never())
            ->method('getIncludedEntities');

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenIncludedEntitiesCollectionDoesNotExist()
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
            ->method('getIncludedData')
            ->willReturn([['key' => 'val']]);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn(null);

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenIncludedEntitiesCollectionIsEmpty()
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
            ->method('getIncludedData')
            ->willReturn([['key' => 'val']]);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn(new IncludedEntityCollection());

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessForNewIncludedObject()
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
            ->method('getIncludedData')
            ->willReturn([['key' => 'val']]);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($includedEntities);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($object), false)
            ->willReturn(null);

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessForExistingIncludedObject()
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
            ->method('getIncludedData')
            ->willReturn([['key' => 'val']]);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($includedEntities);

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessForNewIncludedEntity()
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
            ->method('getIncludedData')
            ->willReturn([['key' => 'val']]);
        $itemTargetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($includedEntities);

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
}
