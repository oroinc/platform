<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Create\SetEntityId;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use PHPUnit\Framework\MockObject\MockObject;

class SetEntityIdTest extends CreateProcessorTestCase
{
    private EntityIdHelper&MockObject $entityIdHelper;
    private SetEntityId $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityIdHelper = $this->createMock(EntityIdHelper::class);

        $this->processor = new SetEntityId($this->entityIdHelper);
    }

    public function testProcessForExistingEntity(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;
        $entity = new \stdClass();
        $metadata = new EntityMetadata($entityClass);

        $this->entityIdHelper->expects(self::never())
            ->method('setEntityIdentifier');

        $this->context->setExisting(true);
        $this->context->setId($entityId);
        $this->context->setClassName($entityClass);
        $this->context->setMetadata($metadata);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenEntityIdDoesNotExistInContext(): void
    {
        $this->entityIdHelper->expects(self::never())
            ->method('setEntityIdentifier');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessWhenEntityDoesNotExistInContext(): void
    {
        $this->entityIdHelper->expects(self::never())
            ->method('setEntityIdentifier');

        $this->context->setId(123);
        $this->processor->process($this->context);
    }

    public function testProcessForUnsupportedEntity(): void
    {
        $this->entityIdHelper->expects(self::never())
            ->method('setEntityIdentifier');

        $this->context->setId(123);
        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    public function testProcessForEntityUsesIdGenerator(): void
    {
        $entityClass = 'Test\Entity';
        $entity = new \stdClass();
        $metadata = new EntityMetadata($entityClass);
        $metadata->setHasIdentifierGenerator(true);

        $this->entityIdHelper->expects(self::never())
            ->method('setEntityIdentifier');

        $this->context->setId(123);
        $this->context->setClassName($entityClass);
        $this->context->setMetadata($metadata);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessForEntityWithoutIdGenerator(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;
        $entity = new \stdClass();
        $metadata = new EntityMetadata($entityClass);

        $this->entityIdHelper->expects(self::once())
            ->method('setEntityIdentifier')
            ->with(self::identicalTo($entity), $entityId, self::identicalTo($metadata));

        $this->context->setId($entityId);
        $this->context->setClassName($entityClass);
        $this->context->setMetadata($metadata);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }
}
