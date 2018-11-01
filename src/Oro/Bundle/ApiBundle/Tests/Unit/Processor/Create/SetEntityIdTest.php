<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Create\SetEntityId;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;

class SetEntityIdTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdHelper */
    private $entityIdHelper;

    /** @var SetEntityId */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->entityIdHelper = $this->createMock(EntityIdHelper::class);

        $this->processor = new SetEntityId($this->entityIdHelper);
    }

    public function testProcessWhenEntityIdDoesNotExistInContext()
    {
        $this->entityIdHelper->expects(self::never())
            ->method('setEntityIdentifier');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessWhenEntityDoesNotExistInContext()
    {
        $this->entityIdHelper->expects(self::never())
            ->method('setEntityIdentifier');

        $this->context->setId(123);
        $this->processor->process($this->context);
    }

    public function testProcessForUnsupportedEntity()
    {
        $this->entityIdHelper->expects(self::never())
            ->method('setEntityIdentifier');

        $this->context->setId(123);
        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    public function testProcessForEntityUsesIdGenerator()
    {
        $entity = new \stdClass();
        $metadata = new EntityMetadata();
        $metadata->setHasIdentifierGenerator(true);

        $this->entityIdHelper->expects(self::never())
            ->method('setEntityIdentifier');

        $this->context->setId(123);
        $this->context->setClassName(get_class($entity));
        $this->context->setMetadata($metadata);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessForEntityWithoutIdGenerator()
    {
        $entityId = 123;
        $entity = new \stdClass();
        $metadata = new EntityMetadata();

        $this->entityIdHelper->expects(self::once())
            ->method('setEntityIdentifier')
            ->with(self::identicalTo($entity), $entityId, self::identicalTo($metadata));

        $this->context->setId($entityId);
        $this->context->setClassName(get_class($entity));
        $this->context->setMetadata($metadata);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }
}
