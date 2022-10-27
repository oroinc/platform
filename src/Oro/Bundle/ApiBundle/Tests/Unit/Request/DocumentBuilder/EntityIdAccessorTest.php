<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\DocumentBuilder;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ArrayAccessor;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\EntityIdAccessor;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;

class EntityIdAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerRegistry */
    private $entityIdTransformerRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerInterface */
    private $entityIdTransformer;

    /** @var EntityIdAccessor */
    private $entityIdAccessor;

    protected function setUp(): void
    {
        $this->entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);

        $this->entityIdAccessor = new EntityIdAccessor(
            new ArrayAccessor(),
            $this->entityIdTransformerRegistry
        );
    }

    public function testGetEntityIdForEntityWithSingleId()
    {
        $entity = ['id' => 123, 'name' => 'val'];
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $requestType = new RequestType([RequestType::REST]);

        $this->entityIdTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with($requestType)
            ->willReturn($this->entityIdTransformer);
        $this->entityIdTransformer->expects(self::once())
            ->method('transform')
            ->with(123, self::identicalTo($metadata))
            ->willReturn('transformedId');

        self::assertEquals(
            'transformedId',
            $this->entityIdAccessor->getEntityId($entity, $metadata, $requestType)
        );
    }

    public function testGetEntityIdForEntityWithSingleZeroId()
    {
        $entity = ['id' => 0, 'name' => 'val'];
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $requestType = new RequestType([RequestType::REST]);

        $this->entityIdTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with($requestType)
            ->willReturn($this->entityIdTransformer);
        $this->entityIdTransformer->expects(self::once())
            ->method('transform')
            ->with(0, self::identicalTo($metadata))
            ->willReturn('0');

        self::assertEquals(
            '0',
            $this->entityIdAccessor->getEntityId($entity, $metadata, $requestType)
        );
    }

    public function testGetEntityIdForEntityWithSingleIdAndEntityDoesNotHaveIdProperty()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'An object of the type "Test\Entity" does not have the identifier property "id".'
        );

        $entity = ['name' => 'val'];
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $requestType = new RequestType([RequestType::REST]);

        $this->entityIdTransformerRegistry->expects(self::never())
            ->method('getEntityIdTransformer');
        $this->entityIdTransformer->expects(self::never())
            ->method('transform');

        $this->entityIdAccessor->getEntityId($entity, $metadata, $requestType);
    }

    public function testGetEntityIdForEntityWithCompositeId()
    {
        $entity = ['id1' => 123, 'id2' => 456, 'name' => 'val'];
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $requestType = new RequestType([RequestType::REST]);

        $this->entityIdTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with($requestType)
            ->willReturn($this->entityIdTransformer);
        $this->entityIdTransformer->expects(self::once())
            ->method('transform')
            ->with(['id1' => 123, 'id2' => 456], self::identicalTo($metadata))
            ->willReturn('transformedId');

        self::assertEquals(
            'transformedId',
            $this->entityIdAccessor->getEntityId($entity, $metadata, $requestType)
        );
    }

    public function testGetEntityIdForEntityWithCompositeIdAndEntityDoesNotHaveOneOfIdProperty()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'An object of the type "Test\Entity" does not have the identifier property "id1".'
        );

        $entity = ['id2' => 456, 'name' => 'val'];
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $requestType = new RequestType([RequestType::REST]);

        $this->entityIdTransformerRegistry->expects(self::never())
            ->method('getEntityIdTransformer');
        $this->entityIdTransformer->expects(self::never())
            ->method('transform');

        $this->entityIdAccessor->getEntityId($entity, $metadata, $requestType);
    }

    public function testGetEntityIdWhenMetadataDoesNotHaveIdInfo()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "Test\Entity" entity does not have an identifier.');

        $entity = ['id' => 123, 'name' => 'val'];
        $metadata = new EntityMetadata('Test\Entity');
        $requestType = new RequestType([RequestType::REST]);

        $this->entityIdTransformerRegistry->expects(self::never())
            ->method('getEntityIdTransformer');
        $this->entityIdTransformer->expects(self::never())
            ->method('transform');

        $this->entityIdAccessor->getEntityId($entity, $metadata, $requestType);
    }

    public function testGetEntityIdWhenIdValueIsNull()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The identifier value for "Test\Entity" entity must not be empty.');

        $entity = ['id' => null, 'name' => 'val'];
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $requestType = new RequestType([RequestType::REST]);

        $this->entityIdTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with($requestType)
            ->willReturn($this->entityIdTransformer);
        $this->entityIdTransformer->expects(self::once())
            ->method('transform')
            ->with(null, self::identicalTo($metadata))
            ->willReturn(null);

        $this->entityIdAccessor->getEntityId($entity, $metadata, $requestType);
    }

    public function testGetEntityIdWhenIdValueIsEmpty()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The identifier value for "Test\Entity" entity must not be empty.');

        $entity = ['id' => 123, 'name' => 'val'];
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $requestType = new RequestType([RequestType::REST]);

        $this->entityIdTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with($requestType)
            ->willReturn($this->entityIdTransformer);
        $this->entityIdTransformer->expects(self::once())
            ->method('transform')
            ->with(123, self::identicalTo($metadata))
            ->willReturn('');

        $this->entityIdAccessor->getEntityId($entity, $metadata, $requestType);
    }

    public function testGetEntityIdWhenSingleEntityIdIsProvidedInsteadOfEntityObject()
    {
        $entityId = 123;
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $requestType = new RequestType([RequestType::REST]);

        $this->entityIdTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with($requestType)
            ->willReturn($this->entityIdTransformer);
        $this->entityIdTransformer->expects(self::once())
            ->method('transform')
            ->with(123, self::identicalTo($metadata))
            ->willReturn('transformedId');

        self::assertEquals(
            'transformedId',
            $this->entityIdAccessor->getEntityId($entityId, $metadata, $requestType)
        );
    }
}
