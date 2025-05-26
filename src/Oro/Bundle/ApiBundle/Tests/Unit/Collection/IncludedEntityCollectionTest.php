<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IncludedEntityCollectionTest extends TestCase
{
    private IncludedEntityData&MockObject $entityData;
    private IncludedEntityCollection $collection;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityData = $this->createMock(IncludedEntityData::class);

        $this->collection = new IncludedEntityCollection();
    }

    public function testShouldIsPrimaryEntityReturnFalseIfPrimaryEntityIdIsNotSet(): void
    {
        self::assertFalse($this->collection->isPrimaryEntity('Test\Class', '123'));
    }

    public function testShouldIsPrimaryEntityReturnFalseIfPrimaryEntityIdIsNull(): void
    {
        $this->collection->setPrimaryEntityId('Test\Class', null);
        self::assertFalse($this->collection->isPrimaryEntity('Test\Class', null));
        self::assertFalse($this->collection->isPrimaryEntity('Test\Class', '123'));
    }

    public function testShouldIsPrimaryEntityReturnTrueIfPrimaryEntityIdIsNotNull(): void
    {
        $this->collection->setPrimaryEntityId('Test\Class', '123');
        self::assertTrue($this->collection->isPrimaryEntity('Test\Class', '123'));
    }

    public function testShouldIsPrimaryEntityReturnTrueIfPrimaryEntityIdIsNotNullWithCompositeId(): void
    {
        $this->collection->setPrimaryEntityId('Test\Class', ['id1' => 'test', 'id2' => 1]);
        self::assertTrue($this->collection->isPrimaryEntity('Test\Class', ['id1' => 'test', 'id2' => 1]));
    }

    public function testShouldIsPrimaryEntityReturnFalseIfPrimaryEntityClassIsNotEqualToGivenClass(): void
    {
        $this->collection->setPrimaryEntityId('Test\Class', '123');
        self::assertFalse($this->collection->isPrimaryEntity('Test\Class1', '123'));
    }

    public function testShouldIsPrimaryEntityReturnFalseIfPrimaryEntityIdIsNotEqualToGivenId(): void
    {
        $this->collection->setPrimaryEntityId('Test\Class', '123');
        self::assertFalse($this->collection->isPrimaryEntity('Test\Class', '456'));
    }

    public function testShouldSetPrimaryEntityThrowExceptionIfPrimaryEntityIdIsNotSetYet(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The primary entity identifier must be set before.');

        $this->collection->setPrimaryEntity(new \stdClass(), null);
    }

    public function testShouldSetPrimaryEntity(): void
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

    public function testShouldBePossibleToGetAlreadySetPrimaryEntity(): void
    {
        $entity = new \stdClass();
        $metadata = new EntityMetadata(get_class($entity));
        $this->collection->setPrimaryEntityId('Test\Class', '123');
        $this->collection->setPrimaryEntity($entity, $metadata);
        self::assertSame($entity, $this->collection->getPrimaryEntity());
        self::assertSame($metadata, $this->collection->getPrimaryEntityMetadata());
    }

    public function testShouldGetReturnNullForUnknownEntity(): void
    {
        self::assertNull($this->collection->get('Test\Class', 'testId'));
    }

    public function testShouldGetAddedEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertSame($entity, $this->collection->get($entityClass, $entityId));
    }

    public function testShouldGetAddedEntityWithCompositeId(): void
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = ['id1' => 'test', 'id2' => 1];
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertSame($entity, $this->collection->get($entityClass, $entityId));
    }

    public function testShouldGetClassReturnNullForUnknownEntity(): void
    {
        self::assertNull($this->collection->getClass(new \stdClass()));
    }

    public function testShouldGetClassOfAddedEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertSame($entityClass, $this->collection->getClass($entity));
    }

    public function testShouldGetIdReturnNullForUnknownEntity(): void
    {
        self::assertNull($this->collection->getId(new \stdClass()));
    }

    public function testShouldGetIdOfAddedEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertSame($entityId, $this->collection->getId($entity));
    }

    public function testShouldGetIdOfAddedEntityWithCompositeId(): void
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = ['id1' => 'test', 'id2' => 1];
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertSame($entityId, $this->collection->getId($entity));
    }

    public function testShouldGetDataReturnNullForUnknownEntity(): void
    {
        self::assertNull($this->collection->getData(new \stdClass()));
    }

    public function testShouldGetDataOfAddedEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertSame($this->entityData, $this->collection->getData($entity));
    }

    public function testShouldContainsReturnFalseForUnknownEntity(): void
    {
        self::assertFalse($this->collection->contains('Test\Class', 'testId'));
    }

    public function testShouldContainsReturnTrueForAddedEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        self::assertTrue($this->collection->contains($entityClass, $entityId));
    }

    public function testShouldBePossibleToGetAllEntities(): void
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        $entities = $this->collection->getAll();
        self::assertCount(1, $entities);
        self::assertSame($entity, $entities[0]);
    }

    public function testShouldBeIterable(): void
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = 'testId';
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        foreach ($this->collection as $v) {
            self::assertSame($entity, $v);
        }
    }

    public function testShouldIsEmptyReturnTrueForEmptyCollection(): void
    {
        self::assertTrue($this->collection->isEmpty());
    }

    public function testShouldIsEmptyReturnFalseForEmptyCollection(): void
    {
        $this->collection->add(new \stdClass(), 'Test\Class', 'testId', $this->entityData);
        self::assertFalse($this->collection->isEmpty());
    }

    public function testShouldCountReturnZeroForEmptyCollection(): void
    {
        self::assertSame(0, $this->collection->count());
    }

    public function testShouldCountReturnTheNumberOfEntitiesInCollection(): void
    {
        $this->collection->add(new \stdClass(), 'Test\Class', 'testId', $this->entityData);
        self::assertSame(1, $this->collection->count());
    }

    public function testShouldBeCountable(): void
    {
        self::assertCount(0, $this->collection);
    }

    public function testShouldClearAllData(): void
    {
        $entity = new \stdClass();
        $this->collection->add($entity, 'Test\Class', 'testId', $this->entityData);
        $this->collection->clear();
        self::assertNull($this->collection->getId($entity));
        self::assertNull($this->collection->getData($entity));
        self::assertTrue($this->collection->isEmpty());
    }

    public function testShouldRemoveNotThrowExceptionForUnknownEntity(): void
    {
        $this->collection->remove('Test\Class', 'testId');
    }

    public function testShouldRemoveEntity(): void
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

    public function testShouldRemoveEntityWithCompositeId(): void
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $entityId = ['id1' => 'test', 'id2' => 1];
        $this->collection->add($entity, $entityClass, $entityId, $this->entityData);
        $this->collection->remove($entityClass, $entityId);
        self::assertNull($this->collection->getId($entity));
        self::assertNull($this->collection->getData($entity));
        self::assertTrue($this->collection->isEmpty());
    }
}
