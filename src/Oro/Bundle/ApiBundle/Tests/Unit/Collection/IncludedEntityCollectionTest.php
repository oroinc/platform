<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;

class IncludedEntityCollectionTest extends \PHPUnit_Framework_TestCase
{
    /** @var IncludedEntityCollection */
    protected $collection;

    /** @var IncludedEntityData */
    protected $entityData;

    protected function setUp()
    {
        $this->entityData = $this->getMockBuilder(IncludedEntityData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection = new IncludedEntityCollection();
    }

    public function testShouldSetPrimaryEntityId()
    {
        $this->collection->setPrimaryEntityId('Test\Class', '123');
        self::assertAttributeSame(['Test\Class', '123', null], 'primaryEntity', $this->collection);
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

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The primary entity identifier must be set before.
     */
    public function testShouldSetPrimaryEntityThrowExceptionIfPrimaryEntityIdIsNotSetYet()
    {
        $this->collection->setPrimaryEntity(new \stdClass());
    }

    public function testShouldSetPrimaryEntity()
    {
        $entityClass = 'Test\Class';
        $entityId = '123';
        $entity = new \stdClass();
        $this->collection->setPrimaryEntityId($entityClass, $entityId);
        $this->collection->setPrimaryEntity($entity);
        self::assertAttributeSame([$entityClass, $entityId, $entity], 'primaryEntity', $this->collection);
    }

    public function testShouldBePossibleToGetAlreadySetPrimaryEntity()
    {
        $entity = new \stdClass();
        $this->collection->setPrimaryEntityId('Test\Class', '123');
        $this->collection->setPrimaryEntity($entity);
        self::assertSame($entity, $this->collection->getPrimaryEntity());
    }

    public function testShouldAddEntity()
    {
        $this->collection->add(new \stdClass(), 'Test\Class', 'testId', $this->entityData);
        self::assertAttributeSame(
            ['Test\Class:testId' => ['Test\Class', 'testId']],
            'keys',
            $this->collection
        );
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

    public function testShouldBeIteratable()
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
        $this->collection->add(new \stdClass(), 'Test\Class', 'testId', $this->entityData);
        $this->collection->clear();
        self::assertAttributeSame([], 'keys', $this->collection);
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
        self::assertAttributeSame([], 'keys', $this->collection);
        self::assertTrue($this->collection->isEmpty());
    }
}
