<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ReminderBundle\Entity\Collection\RemindersPersistentCollection;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RemindersPersistentCollectionTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = 'Foo\\Entity';
    private const IDENTIFIER = 101;

    /** @var MockObject|ReminderRepository */
    protected $repository;

    /** @var RemindersPersistentCollection */
    protected $collection;

    protected function setUp(): void
    {
        $this->repository = $this->getMockBuilder(ReminderRepository::class)->disableOriginalConstructor()->getMock();
        $this->collection = new class(
            $this->repository,
            self::CLASS_NAME,
            self::IDENTIFIER
        ) extends RemindersPersistentCollection {
            public function xgetCollection(): Collection
            {
                return $this->collection;
            }
        };
    }

    public function testAdd()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $this->expectInitialize([$foo]);
        $this->collection->add($bar);

        static::assertCollectionElementsEquals([$foo, $bar]);
        static::assertTrue($this->collection->isDirty());
    }

    public function testClear()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize([$foo, $bar]);
        $this->collection->clear();

        static::assertCollectionElementsEquals([]);
        static::assertTrue($this->collection->isDirty());
    }

    public function testRemove()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize([$foo, $bar]);
        $this->collection->remove(3);

        static::assertCollectionElementsEquals([$foo, $bar]);
        static::assertFalse($this->collection->isDirty());

        $this->collection->remove(0);
        static::assertCollectionElementsEquals([1 => $bar]);
        static::assertTrue($this->collection->isDirty());
    }

    public function testRemoveElement()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize([$foo, $bar]);
        $this->collection->removeElement($baz);

        static::assertCollectionElementsEquals([$foo, $bar]);
        static::assertFalse($this->collection->isDirty());

        $this->collection->removeElement($foo);
        static::assertCollectionElementsEquals([1 => $bar]);
        static::assertTrue($this->collection->isDirty());
    }

    public function testSet()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize([$foo]);
        $this->collection->set(0, $bar);
        static::assertCollectionElementsEquals([$bar]);
        static::assertTrue($this->collection->isDirty());

        $this->collection->set(null, $baz);
        static::assertCollectionElementsEquals([$bar, $baz]);
        static::assertTrue($this->collection->isDirty());
    }

    public function testGetSnapshot()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        static::assertEquals([], $this->collection->getSnapshot());
        $this->expectInitialize([$foo, $bar]);

        $this->collection->isEmpty();
        static::assertEquals([$foo, $bar], $this->collection->getSnapshot());
    }

    public function testOffsetExists()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize([$foo, $bar]);
        static::assertTrue(isset($this->collection[0]));
        static::assertTrue(isset($this->collection[1]));
        static::assertFalse(isset($this->collection[2]));
        static::assertFalse($this->collection->isDirty());
    }

    public function testOffsetGet()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize([$foo, $bar]);
        static::assertEquals($foo, $this->collection[0]);
        static::assertEquals($bar, $this->collection[1]);
        static::assertNull($this->collection[2]);
        static::assertFalse($this->collection->isDirty());
    }

    public function testOffsetSet()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize([$foo, $bar]);
        $this->collection[0] = $baz;
        static::assertEquals($baz, $this->collection[0]);
        static::assertCollectionElementsEquals([$baz, $bar]);
        static::assertTrue($this->collection->isDirty());
    }

    public function testOffsetUnset()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize([$foo, $bar]);
        unset($this->collection[0]);
        static::assertCollectionElementsEquals([1 => $bar]);
        static::assertTrue($this->collection->isDirty());
    }

    public function testGetDeleteDiff()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize([$foo, $bar, $baz]);

        static::assertEquals([], $this->collection->getDeleteDiff());

        $this->collection->removeElement($bar);
        $this->collection->removeElement($baz);

        static::assertEquals([1 => $bar, $baz], $this->collection->getDeleteDiff());
    }

    public function testGetInsertDiff()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize([$foo]);

        static::assertEquals([], $this->collection->getInsertDiff());

        $this->collection->add($bar);
        $this->collection->add($baz);

        static::assertEquals([1 => $bar, $baz], $this->collection->getInsertDiff());
    }

    protected function expectInitialize(array $reminders)
    {
        $this->repository->expects($this->once())
            ->method('findRemindersByEntity')
            ->with(self::CLASS_NAME, self::IDENTIFIER)
            ->willReturn($reminders);
    }

    protected function assertCollectionElementsEquals(array $elements)
    {
        static::assertEquals(new ArrayCollection($elements), $this->collection->xgetCollection());
    }

    protected function createReminder($id)
    {
        $result = $this->createMock(Reminder::class);
        $result->method('getId')->willReturn($id);

        return $result;
    }
}
