<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\JsonApi\JsonApiDocument;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ArrayAccessor;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\EntityIdAccessor;

class EntityIdAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdTransformer;

    /** @var EntityIdAccessor */
    protected $entityIdAccessor;

    protected function setUp()
    {
        $this->entityIdTransformer = $this->getMock('Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface');

        $this->entityIdAccessor = new EntityIdAccessor(
            new ArrayAccessor(),
            $this->entityIdTransformer
        );
    }

    public function testGetEntityIdForEntityWithSingleId()
    {
        $entity   = ['id' => 123, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->entityIdTransformer->expects($this->once())
            ->method('transform')
            ->with(123)
            ->willReturn('transformedId');

        $this->assertEquals(
            'transformedId',
            $this->entityIdAccessor->getEntityId($entity, $metadata)
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage An object of the type "Test\Entity" does not have the identifier property "id".
     */
    public function testGetEntityIdForEntityWithSingleIdAndEntityDoesNotHaveIdProperty()
    {
        $entity   = ['name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->entityIdTransformer->expects($this->never())
            ->method('transform');

        $this->entityIdAccessor->getEntityId($entity, $metadata);
    }

    public function testGetEntityIdForEntityWithCompositeId()
    {
        $entity   = ['id1' => 123, 'id2' => 456, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
        $metadata->setIdentifierFieldNames(['id1', 'id2']);

        $this->entityIdTransformer->expects($this->once())
            ->method('transform')
            ->with(['id1' => 123, 'id2' => 456])
            ->willReturn('transformedId');

        $this->assertEquals(
            'transformedId',
            $this->entityIdAccessor->getEntityId($entity, $metadata)
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage An object of the type "Test\Entity" does not have the identifier property "id1".
     */
    public function testGetEntityIdForEntityWithCompositeIdAndEntityDoesNotHaveOneOfIdProperty()
    {
        $entity   = ['id2' => 456, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
        $metadata->setIdentifierFieldNames(['id1', 'id2']);

        $this->entityIdTransformer->expects($this->never())
            ->method('transform');

        $this->entityIdAccessor->getEntityId($entity, $metadata);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "Test\Entity" entity does not have an identifier.
     */
    public function testGetEntityIdWhenMetadataDoesNotHaveIdInfo()
    {
        $entity   = ['id' => 123, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');

        $this->entityIdAccessor->getEntityId($entity, $metadata);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The identifier value for "Test\Entity" entity must not be empty.
     */
    public function testGetEntityIdWhenIdValueIsNull()
    {
        $entity   = ['id' => null, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->entityIdTransformer->expects($this->once())
            ->method('transform')
            ->with(null)
            ->willReturn(null);

        $this->entityIdAccessor->getEntityId($entity, $metadata);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The identifier value for "Test\Entity" entity must not be empty.
     */
    public function testGetEntityIdWhenIdValueIsEmpty()
    {
        $entity   = ['id' => 123, 'name' => 'val'];
        $metadata = new EntityMetadata();
        $metadata->setClassName('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->entityIdTransformer->expects($this->once())
            ->method('transform')
            ->with(123)
            ->willReturn('');

        $this->entityIdAccessor->getEntityId($entity, $metadata);
    }
}
