<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\UpdateSummaryCounters;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use PHPUnit\Framework\MockObject\MockObject;

class UpdateSummaryCountersTest extends BatchUpdateProcessorTestCase
{
    private EntityIdHelper&MockObject $entityIdHelper;
    private UpdateSummaryCounters $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityIdHelper = $this->createMock(EntityIdHelper::class);

        $this->processor = new UpdateSummaryCounters($this->entityIdHelper);
    }

    private function expectsAffectedEntities(
        BatchUpdateItemContext $itemContext,
        mixed $primaryEntityId,
        mixed $primaryEntityRequestId,
        mixed $includedEntityClass,
        mixed $includedEntityId,
        mixed $includedEntityRequestId,
        mixed $isExistingPrimaryEntity = false,
        mixed $isExistingIncludedEntity = false
    ): void {
        $primaryEntity = $this->createMock(\stdClass::class);
        $primaryEntityMetadata = $this->createMock(EntityMetadata::class);
        $targetContext = $this->createMock(CreateContext::class);
        $itemContext->setTargetContext($targetContext);
        $targetContext->expects(self::once())
            ->method('getResult')
            ->willReturn($primaryEntity);
        $targetContext->expects(self::once())
            ->method('getRequestId')
            ->willReturn($primaryEntityRequestId);
        $targetContext->expects(self::once())
            ->method('getMetadata')
            ->willReturn($primaryEntityMetadata);
        $targetContext->expects(self::once())
            ->method('isExisting')
            ->willReturn($isExistingPrimaryEntity);

        $includedEntity = $this->createMock(\stdClass::class);
        $includedEntityMetadata = $this->createMock(EntityMetadata::class);
        $includedEntities = new IncludedEntityCollection();
        $targetContext->expects(self::once())
            ->method('getIncludedEntities')
            ->willReturn($includedEntities);
        $includedEntities->setPrimaryEntityId(\stdClass::class, $primaryEntityId);
        $includedEntities->setPrimaryEntity($primaryEntity, $primaryEntityMetadata);
        $includedEntityData = new IncludedEntityData('/included/0', 0, $isExistingIncludedEntity);
        $includedEntityData->setMetadata($includedEntityMetadata);
        $includedEntities->add($includedEntity, \stdClass::class, $includedEntityRequestId, $includedEntityData);

        $includedEntityMetadata->expects(self::once())
            ->method('getClassName')
            ->willReturn($includedEntityClass);

        $this->entityIdHelper->expects(self::exactly(2))
            ->method('getEntityIdentifier')
            ->withConsecutive(
                [self::identicalTo($primaryEntity), self::identicalTo($primaryEntityMetadata)],
                [self::identicalTo($includedEntity), self::identicalTo($includedEntityMetadata)]
            )
            ->willReturnOnConsecutiveCalls(
                $primaryEntityId,
                $includedEntityId
            );
    }

    public function testProcessWithoutBatchItems(): void
    {
        $this->context->setProcessedItemStatuses([]);
        $this->processor->process($this->context);

        self::assertSame(0, $this->context->getSummary()->getWriteCount());
        self::assertSame(0, $this->context->getSummary()->getCreateCount());
        self::assertSame(0, $this->context->getSummary()->getUpdateCount());
    }

    public function testProcessWithCreateBatchItem(): void
    {
        $itemContext = new BatchUpdateItemContext();
        $itemContext->setTargetAction(ApiAction::CREATE);
        $item = $this->createMock(BatchUpdateItem::class);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);

        $primaryEntityId = 123;
        $primaryEntityRequestId = '123';
        $includedEntityClass = 'Test\IncludedEntity';
        $includedEntityId = 234;
        $includedEntityRequestId = '234';
        $this->expectsAffectedEntities(
            $itemContext,
            $primaryEntityId,
            $primaryEntityRequestId,
            $includedEntityClass,
            $includedEntityId,
            $includedEntityRequestId
        );

        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([BatchUpdateItemStatus::NO_ERRORS]);
        $this->processor->process($this->context);

        self::assertSame(1, $this->context->getSummary()->getWriteCount());
        self::assertSame(1, $this->context->getSummary()->getCreateCount());
        self::assertSame(0, $this->context->getSummary()->getUpdateCount());

        self::assertSame(
            [[$primaryEntityId, $primaryEntityRequestId, false]],
            $this->context->getAffectedEntities()->getPrimaryEntities()
        );
        self::assertSame(
            [[$includedEntityClass, $includedEntityId, $includedEntityRequestId, false]],
            $this->context->getAffectedEntities()->getIncludedEntities()
        );
    }

    public function testProcessWithUpdateBatchItem(): void
    {
        $itemContext = new BatchUpdateItemContext();
        $itemContext->setTargetAction(ApiAction::UPDATE);
        $item = $this->createMock(BatchUpdateItem::class);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);

        $primaryEntityId = 123;
        $primaryEntityRequestId = '123';
        $includedEntityClass = 'Test\IncludedEntity';
        $includedEntityId = 234;
        $includedEntityRequestId = '234';
        $this->expectsAffectedEntities(
            $itemContext,
            $primaryEntityId,
            $primaryEntityRequestId,
            $includedEntityClass,
            $includedEntityId,
            $includedEntityRequestId,
            true,
            true
        );

        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([BatchUpdateItemStatus::NO_ERRORS]);
        $this->processor->process($this->context);

        self::assertSame(1, $this->context->getSummary()->getWriteCount());
        self::assertSame(0, $this->context->getSummary()->getCreateCount());
        self::assertSame(1, $this->context->getSummary()->getUpdateCount());

        self::assertSame(
            [[$primaryEntityId, $primaryEntityRequestId, true]],
            $this->context->getAffectedEntities()->getPrimaryEntities()
        );
        self::assertSame(
            [[$includedEntityClass, $includedEntityId, $includedEntityRequestId, true]],
            $this->context->getAffectedEntities()->getIncludedEntities()
        );
    }

    public function testProcessWithUnknownBatchItem(): void
    {
        $itemContext = new BatchUpdateItemContext();
        $item = $this->createMock(BatchUpdateItem::class);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);

        $primaryEntityId = 123;
        $primaryEntityRequestId = '123';
        $includedEntityClass = 'Test\IncludedEntity';
        $includedEntityId = 234;
        $includedEntityRequestId = '234';
        $this->expectsAffectedEntities(
            $itemContext,
            $primaryEntityId,
            $primaryEntityRequestId,
            $includedEntityClass,
            $includedEntityId,
            $includedEntityRequestId
        );

        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([BatchUpdateItemStatus::NO_ERRORS]);
        $this->processor->process($this->context);

        self::assertSame(0, $this->context->getSummary()->getWriteCount());
        self::assertSame(0, $this->context->getSummary()->getCreateCount());
        self::assertSame(0, $this->context->getSummary()->getUpdateCount());

        self::assertSame(
            [[$primaryEntityId, $primaryEntityRequestId, false]],
            $this->context->getAffectedEntities()->getPrimaryEntities()
        );
        self::assertSame(
            [[$includedEntityClass, $includedEntityId, $includedEntityRequestId, false]],
            $this->context->getAffectedEntities()->getIncludedEntities()
        );
    }

    public function testProcessWhenErrorOccurredWhenProcessingBatchItem(): void
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $item->expects(self::once())
            ->method('getIndex')
            ->willReturn(0);
        $item->expects(self::never())
            ->method('getContext');

        $this->context->setBatchItems([$item]);
        $this->context->setProcessedItemStatuses([BatchUpdateItemStatus::HAS_ERRORS]);
        $this->processor->process($this->context);

        self::assertSame(0, $this->context->getSummary()->getWriteCount());
        self::assertSame(0, $this->context->getSummary()->getCreateCount());
        self::assertSame(0, $this->context->getSummary()->getUpdateCount());
    }
}
