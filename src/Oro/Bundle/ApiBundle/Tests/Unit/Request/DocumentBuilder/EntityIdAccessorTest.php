<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\DocumentBuilder;

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

    protected function setUp()
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
        $entity   = ['id' => 123, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
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

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage An object of the type "Test\Entity" does not have the identifier property "id".
     */
    public function testGetEntityIdForEntityWithSingleIdAndEntityDoesNotHaveIdProperty()
    {
        $entity   = ['name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
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
        $entity   = ['id1' => 123, 'id2' => 456, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
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

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage An object of the type "Test\Entity" does not have the identifier property "id1".
     */
    public function testGetEntityIdForEntityWithCompositeIdAndEntityDoesNotHaveOneOfIdProperty()
    {
        $entity   = ['id2' => 456, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $requestType = new RequestType([RequestType::REST]);

        $this->entityIdTransformerRegistry->expects(self::never())
            ->method('getEntityIdTransformer');
        $this->entityIdTransformer->expects(self::never())
            ->method('transform');

        $this->entityIdAccessor->getEntityId($entity, $metadata, $requestType);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "Test\Entity" entity does not have an identifier.
     */
    public function testGetEntityIdWhenMetadataDoesNotHaveIdInfo()
    {
        $entity   = ['id' => 123, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
        $requestType = new RequestType([RequestType::REST]);

        $this->entityIdTransformerRegistry->expects(self::never())
            ->method('getEntityIdTransformer');
        $this->entityIdTransformer->expects(self::never())
            ->method('transform');

        $this->entityIdAccessor->getEntityId($entity, $metadata, $requestType);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The identifier value for "Test\Entity" entity must not be empty.
     */
    public function testGetEntityIdWhenIdValueIsNull()
    {
        $entity   = ['id' => null, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
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

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The identifier value for "Test\Entity" entity must not be empty.
     */
    public function testGetEntityIdWhenIdValueIsEmpty()
    {
        $entity   = ['id' => 123, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
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
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
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
