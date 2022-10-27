<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IncludedEntityCollectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var IncludedEntityCollection */
    private $collection;

    /** @var IncludedEntityData */
    private $entityData;

    protected function setUp(): void
    {
        $this->entityData = $this->createMock(IncludedEntityData::class);

        $this->collection = new IncludedEntityCollection();
    }

    public function testShouldIsPrimaryEntityReturnFalseIfPrimaryEntityIdIsNotSet()
    {
        self::assertFalse($this->collection->isPrimaryEntity('Test\Class', '123'));
    }

    public function testShouldIsPrimaryEntityReturnFalseIfPrimaryEntityIdIsNull()
    {
        $this->collection->setPrimaryEntityId('Test\Class', null);
        self::assertFalse($this->collection->isPrimaryEntity('Test\Class', null));
        self::assertFalse($this->collection->isPrimaryEntity('Test\Class', '123'));
    }

    public function testShouldIsPrimaryEntityReturnTrueIfPrimaryEntityIdIsNotNull()
    {
        $this->collection->setPrimaryEntityId('Test\Class', '123');
        self::assertTrue($this->collection->isPrimaryEntity('Test\Class', '123'));
    }

    public function testShouldIsPrimaryEntityReturnFalseIfPrimaryEntityClassIsNotEqualToGivenClass()
    {
        $this->collection->setPrimaryEntityId('Test\Class', '123');
        self::assertFalse($this->collection->isPrimaryEntity('Test\Class1', '123'));
    }

    public function testShouldIsPrimaryEntityReturnFalseIfPrimaryEntityIdIsNotEqualToGivenId()
    {
        $this->collection->setPrimaryEntityId('Test\Class', '123');
        self::assertFalse($this->collection->isPrimaryEntity('Test\Class', '456'));
    }

    public function testShouldSetPrimaryEntityThrowExceptionIfPrimaryEntityIdIsNotSetYet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The primary entity identifier must be set before.');

        $this->collection->setPrimaryEntity(new \stdClass(), null);
    }

    public function testShouldSetPrimaryEntity()
    {
        $entityClass = 'Test\Class';
        $entityId = '123';
        $entity = new \stdClass();
        $metadata = new EntityMetadata(get_class($entity));
        $this->collection->setPrimaryEntityId($entityClass, $entityId);
        $this->collection->setPrimaryEntity($entity, $metadata);
        self::assertTrue($this->collection->isPrimaryEntity('Test\Class', '123'));
        self::assertSame($entity, $this->collection->getPrimaryEntity());
        self::assertSame($metadata, $this->collection->getPrimaryEntityMetadata());
    }

    public function testShouldBePossibleToGetAlreadySetPrimaryEntity()
    {
        $entity = new \stdClass();
        $metadata = new EntityMetadata(get_class($entity));
        $this->collection->setPrimaryEntityId('Test\Class', '123');
        $this->collection->setPrimaryEntity($entity, $metadata);
        self::assertSame($entity, $this->collection->getPrimaryEntity());
        self::assertSame($metadata, $this->collection->getPrimaryEntityMetadata());
    }

    public function testShouldGetReturnNullForUnknownEntity()
    {
        self::assertNull($this->collection->get('Test\Class', 'testId'));
    }

    public function testShouldGetAddedEntity()
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertSame($entity, $this->collection->get($entityClass, $entityId));
    }

    public function testShouldGetClassReturnNullForUnknownEntity()
    {
        self::assertNull($this->collection->getClass(new \stdClass()));
    }

    public function testShouldGetClassOfAddedEntity()
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertSame($entityClass, $this->collection->getClass($entity));
    }

    public function testShouldGetIdReturnNullForUnknownEntity()
    {
        self::assertNull($this->collection->getId(new \stdClass()));
    }

    public function testShouldGetIdOfAddedEntity()
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertSame($entityId, $this->collection->getId($entity));
    }

    public function testShouldGetDataReturnNullForUnknownEntity()
    {
        self::assertNull($this->collection->getData(new \stdClass()));
    }

    public function testShouldGetDataOfAddedEntity()
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertSame($this->entityData, $this->collection->getData($entity));
    }

    public function testShouldContainsReturnFalseForUnknownEntity()
    {
        self::assertFalse($this->collection->contains('Test\Class', 'testId'));
    }

    public function testShouldContainsReturnTrueForAddedEntity()
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertTrue($this->collection->contains($entityClass, $entityId));
    }

    public function testShouldBePossibleToGetAllEntities()
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        $entities = $this->collection->getAll();
        self::assertIsArray($entities);
        self::assertCount(1, $entities);
        self::assertSame($entity, $entities[0]);
    }

    public function testShouldBeIterable()
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        foreach ($this->collection as $v) {
            self::assertSame($entity, $v);
        }
    }

    public function testShouldIsEmptyReturnTrueForEmptyCollection()
    {
        self::assertTrue($this->collection->isEmpty());
    }

    public function testShouldIsEmptyReturnFalseForEmptyCollection()
    {
        $this->collection->add(new \stdClass(), 'Test\Class', 'testId', $this->entityData);
        self::assertFalse($this->collection->isEmpty());
    }

    public function testShouldCountReturnZeroForEmptyCollection()
    {
        self::assertSame(0, $this->collection->count());
    }

    public function testShouldCountReturnTheNumberOfEntitiesInCollection()
    {
        $this->collection->add(new \stdClass(), 'Test\Class', 'testId', $this->entityData);
        self::assertSame(1, $this->collection->count());
    }

    public function testShouldBeCountable()
    {
        self::assertCount(0, $this->collection);
    }

    public function testShouldClearAllData()
    {
        $entity = new \stdClass();
        $this->collection->add($entity, 'Test\Class', 'testId', $this->entityData);
        $this->collection->clear();
        self::assertNull($this->collection->getId($entity));
        self::assertNull($this->collection->getData($entity));
        self::assertTrue($this->collection->isEmpty());
    }

    public function testShouldRemoveNotThrowExceptionForUnknownEntity()
    {
        $this->collection->remove('Test\Class', 'testId');
    }

    public function testShouldRemoveEntity()
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        $this->collection->remove($entityClass, $entityId);
        self::assertNull($this->collection->getId($entity));
        self::assertNull($this->collection->getData($entity));
        self::assertTrue($this->collection->isEmpty());
    }
}
