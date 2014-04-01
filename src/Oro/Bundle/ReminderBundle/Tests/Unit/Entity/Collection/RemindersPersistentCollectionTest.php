<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ReminderBundle\Entity\Collection\RemindersPersistentCollection;

class RemindersPersistentCollectionTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'Foo\\Entity';
    const IDENTIFIER = 101;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var RemindersPersistentCollection
     */
    protected $collection;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder('Oro\\Bundle\\ReminderBundle\\Entity\\Repository\\ReminderRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = new RemindersPersistentCollection($this->repository, self::CLASS_NAME, self::IDENTIFIER);
    }

    public function testAdd()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $this->expectInitialize(array($foo));
        $this->collection->add($bar);

        $this->assertCollectionElementsEquals(array($foo, $bar));
        $this->assertTrue($this->collection->isDirty());
    }

    public function testClear()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize(array($foo, $bar));
        $this->collection->clear();

        $this->assertCollectionElementsEquals(array());
        $this->assertTrue($this->collection->isDirty());
    }

    public function testRemove()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize(array($foo, $bar));
        $this->collection->remove(3);

        $this->assertCollectionElementsEquals(array($foo, $bar));
        $this->assertFalse($this->collection->isDirty());

        $this->collection->remove(0);
        $this->assertCollectionElementsEquals(array(1 => $bar));
        $this->assertTrue($this->collection->isDirty());
    }

    public function testRemoveElement()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize(array($foo, $bar));
        $this->collection->removeElement($baz);

        $this->assertCollectionElementsEquals(array($foo, $bar));
        $this->assertFalse($this->collection->isDirty());

        $this->collection->removeElement($foo);
        $this->assertCollectionElementsEquals(array(1 => $bar));
        $this->assertTrue($this->collection->isDirty());
    }

    public function testSet()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize(array($foo));
        $this->collection->set(0, $bar);
        $this->assertCollectionElementsEquals(array($bar));
        $this->assertTrue($this->collection->isDirty());

        $this->collection->set(null, $baz);
        $this->assertCollectionElementsEquals(array($bar, $baz));
        $this->assertTrue($this->collection->isDirty());
    }

    public function testGetSnapshot()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->assertEquals(array(), $this->collection->getSnapshot());
        $this->expectInitialize(array($foo, $bar));

        $this->collection->isEmpty();
        $this->assertEquals(array($foo, $bar), $this->collection->getSnapshot());
    }

    public function testOffsetExists()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize(array($foo, $bar));
        $this->assertTrue(isset($this->collection[0]));
        $this->assertTrue(isset($this->collection[1]));
        $this->assertFalse(isset($this->collection[2]));
        $this->assertFalse($this->collection->isDirty());
    }

    public function testOffsetGet()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize(array($foo, $bar));
        $this->assertEquals($foo, $this->collection[0]);
        $this->assertEquals($bar, $this->collection[1]);
        $this->assertNull($this->collection[2]);
        $this->assertFalse($this->collection->isDirty());
    }

    public function testOffsetSet()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize(array($foo, $bar));
        $this->collection[0] = $baz;
        $this->assertEquals($baz, $this->collection[0]);
        $this->assertCollectionElementsEquals(array($baz, $bar));
        $this->assertTrue($this->collection->isDirty());
    }

    public function testOffsetUnset()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize(array($foo, $bar));
        unset($this->collection[0]);
        $this->assertCollectionElementsEquals(array(1 => $bar));
        $this->assertTrue($this->collection->isDirty());
    }

    public function testGetDeleteDiff()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize(array($foo, $bar, $baz));

        $this->assertEquals(array(), $this->collection->getDeleteDiff());

        $this->collection->removeElement($bar);
        $this->collection->removeElement($baz);

        $this->assertEquals(array(1 => $bar, $baz), $this->collection->getDeleteDiff());
    }

    public function testGetInsertDiff()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize(array($foo));

        $this->assertEquals(array(), $this->collection->getInsertDiff());

        $this->collection->add($bar);
        $this->collection->add($baz);

        $this->assertEquals(array(1 => $bar, $baz), $this->collection->getInsertDiff());
    }

    protected function expectInitialize(array $reminders)
    {
        $this->repository->expects($this->once())
            ->method('findRemindersByEntity')
            ->with(self::CLASS_NAME, self::IDENTIFIER)
            ->will($this->returnValue($reminders));
    }

    protected function assertCollectionElementsEquals(array $elements)
    {
        $this->assertAttributeEquals(new ArrayCollection($elements), 'collection', $this->collection);
    }

    protected function createReminder($id)
    {
        $result = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');
        $result->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        return $result;
    }
}
