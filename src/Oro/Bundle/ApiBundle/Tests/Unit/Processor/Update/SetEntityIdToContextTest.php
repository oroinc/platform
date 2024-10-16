<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Update\SetEntityIdToContext;

class SetEntityIdToContextTest extends UpdateProcessorTestCase
{
    private SetEntityIdToContext $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetEntityIdToContext();
    }

    public function testProcessWhenIdAlreadySet(): void
    {
        $entityId = 123;

        $metadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::never())
            ->method('getIdentifierValue');

        $this->context->setResult(new \stdClass());
        $this->context->setMetadata($metadata);
        $this->context->setId($entityId);
        $this->context->setProcessed(SetEntityIdToContext::OPERATION_NAME);
        $this->processor->process($this->context);

        self::assertEquals($entityId, $this->context->getId());
    }

    public function testProcessForExistingEntity(): void
    {
        $entityId = 123;

        $metadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::never())
            ->method('getIdentifierFieldNames');
        $metadata->expects(self::never())
            ->method('getIdentifierValue');

        $this->context->setId($entityId);
        $this->context->setResult(new \stdClass());
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals($entityId, $this->context->getId());
        self::assertFalse($this->context->isProcessed(SetEntityIdToContext::OPERATION_NAME));
    }

    public function testProcessWhenNoEntity(): void
    {
        $metadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::never())
            ->method('getIdentifierValue');

        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
        self::assertFalse($this->context->isProcessed(SetEntityIdToContext::OPERATION_NAME));
    }

    public function testProcessWhenNoApiMetadata(): void
    {
        $this->context->setExisting(false);
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
        self::assertFalse($this->context->isProcessed(SetEntityIdToContext::OPERATION_NAME));
    }

    public function testProcessForNewEntityWithoutIdentifier(): void
    {
        $entity = new \stdClass();

        $metadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::never())
            ->method('getIdentifierValue');

        $this->context->setExisting(false);
        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
        self::assertFalse($this->context->isProcessed(SetEntityIdToContext::OPERATION_NAME));
    }

    public function testProcessForNewEntityWithSingleId(): void
    {
        $entity = new \stdClass();
        $entityId = 123;

        $metadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $metadata->expects(self::once())
            ->method('getIdentifierValue')
            ->with(self::identicalTo($entity))
            ->willReturn($entityId);

        $this->context->setExisting(false);
        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals($entityId, $this->context->getId());
        self::assertTrue($this->context->isProcessed(SetEntityIdToContext::OPERATION_NAME));
    }

    public function testProcessForNewEntityWithCompositeId(): void
    {
        $entity = new \stdClass();
        $entityId = ['id1' => 1, 'id2' => 2];

        $metadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id1', 'id2']);
        $metadata->expects(self::once())
            ->method('getIdentifierValue')
            ->with(self::identicalTo($entity))
            ->willReturn($entityId);

        $this->context->setExisting(false);
        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals($entityId, $this->context->getId());
        self::assertTrue($this->context->isProcessed(SetEntityIdToContext::OPERATION_NAME));
    }

    public function testProcessWhenNewEntityIdIsNull(): void
    {
        $entity = new \stdClass();

        $metadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $metadata->expects(self::once())
            ->method('getIdentifierValue')
            ->with(self::identicalTo($entity))
            ->willReturn(null);

        $this->context->setExisting(false);
        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
        self::assertTrue($this->context->isProcessed(SetEntityIdToContext::OPERATION_NAME));
    }
}
