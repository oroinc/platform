<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\IncludedObjectCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedObjectData;

class IncludedObjectCollectionTest extends \PHPUnit_Framework_TestCase
{
    /** @var IncludedObjectCollection */
    protected $collection;

    /** @var IncludedObjectData */
    protected $objectData;

    protected function setUp()
    {
        $this->objectData = $this->getMockBuilder(IncludedObjectData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection = new IncludedObjectCollection();
    }

    public function testShouldAddObject()
    {
        $this->collection->add(new \stdClass(), 'Test\Class', 'testId', $this->objectData);
        self::assertAttributeSame(
            ['Test\Class:testId' => ['Test\Class', 'testId']],
            'keys',
            $this->collection
        );
    }

    public function testShouldGetReturnNullForUnknownObject()
    {
        self::assertNull($this->collection->get('Test\Class', 'testId'));
    }

    public function testShouldGetAddedObject()
    {
        $object = new \stdClass();
        $objectClass = 'Test\Class';
        $objectId = 'testId';
        $this->collection->add($object, $objectClass, $objectId, $this->objectData);
        self::assertSame($object, $this->collection->get($objectClass, $objectId));
    }

    public function testShouldGetClassReturnNullForUnknownObject()
    {
        self::assertNull($this->collection->getClass(new \stdClass()));
    }

    public function testShouldGetClassOfAddedObject()
    {
        $object = new \stdClass();
        $objectClass = 'Test\Class';
        $objectId = 'testId';
        $this->collection->add($object, $objectClass, $objectId, $this->objectData);
        self::assertSame($objectClass, $this->collection->getClass($object));
    }

    public function testShouldGetIdReturnNullForUnknownObject()
    {
        self::assertNull($this->collection->getId(new \stdClass()));
    }

    public function testShouldGetIdOfAddedObject()
    {
        $object = new \stdClass();
        $objectClass = 'Test\Class';
        $objectId = 'testId';
        $this->collection->add($object, $objectClass, $objectId, $this->objectData);
        self::assertSame($objectId, $this->collection->getId($object));
    }

    public function testShouldGetDataReturnNullForUnknownObject()
    {
        self::assertNull($this->collection->getData(new \stdClass()));
    }

    public function testShouldGetDataOfAddedObject()
    {
        $object = new \stdClass();
        $objectClass = 'Test\Class';
        $objectId = 'testId';
        $this->collection->add($object, $objectClass, $objectId, $this->objectData);
        self::assertSame($this->objectData, $this->collection->getData($object));
    }

    public function testShouldContainsReturnFalseForUnknownObject()
    {
        self::assertFalse($this->collection->contains('Test\Class', 'testId'));
    }

    public function testShouldContainsReturnTrueForAddedObject()
    {
        $object = new \stdClass();
        $objectClass = 'Test\Class';
        $objectId = 'testId';
        $this->collection->add($object, $objectClass, $objectId, $this->objectData);
        self::assertTrue($this->collection->contains($objectClass, $objectId));
    }

    public function testShouldBeIteratable()
    {
        $object = new \stdClass();
        $objectClass = 'Test\Class';
        $objectId = 'testId';
        $this->collection->add($object, $objectClass, $objectId, $this->objectData);
        foreach ($this->collection as $v) {
            self::assertSame($object, $v);
        }
    }

    public function testShouldIsEmptyReturnTrueForEmptyCollection()
    {
        self::assertTrue($this->collection->isEmpty());
    }

    public function testShouldIsEmptyReturnFalseForEmptyCollection()
    {
        $this->collection->add(new \stdClass(), 'Test\Class', 'testId', $this->objectData);
        self::assertFalse($this->collection->isEmpty());
    }

    public function testShouldCountReturnZeroForEmptyCollection()
    {
        self::assertSame(0, $this->collection->count());
    }

    public function testShouldCountReturnTheNumberOfObjectsInCollection()
    {
        $this->collection->add(new \stdClass(), 'Test\Class', 'testId', $this->objectData);
        self::assertSame(1, $this->collection->count());
    }

    public function testShouldBeCountable()
    {
        self::assertCount(0, $this->collection);
    }

    public function testShouldClearAllData()
    {
        $this->collection->add(new \stdClass(), 'Test\Class', 'testId', $this->objectData);
        $this->collection->clear();
        self::assertAttributeSame([], 'keys', $this->collection);
        self::assertTrue($this->collection->isEmpty());
    }

    public function testShouldRemoveNotThrowExceptionForUnknownObject()
    {
        $this->collection->remove('Test\Class', 'testId');
    }

    public function testShouldRemoveObject()
    {
        $object = new \stdClass();
        $objectClass = 'Test\Class';
        $objectId = 'testId';
        $this->collection->add($object, $objectClass, $objectId, $this->objectData);
        $this->collection->remove($objectClass, $objectId);
        self::assertAttributeSame([], 'keys', $this->collection);
        self::assertTrue($this->collection->isEmpty());
    }
}
