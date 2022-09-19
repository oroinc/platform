<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ReminderBundle\Entity\Collection\RemindersPersistentCollection;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RemindersPersistentCollectionTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = 'Foo\\Entity';
    private const IDENTIFIER = 101;

    /** @var ReminderRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var RemindersPersistentCollection */
    private $collection;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ReminderRepository::class);
        $this->collection = new RemindersPersistentCollection(
            $this->repository,
            self::CLASS_NAME,
            self::IDENTIFIER
        );
    }

    public function testAdd()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $this->expectInitialize([$foo]);
        $this->collection->add($bar);

        $this->assertCollectionElementsEquals([$foo, $bar]);
        self::assertTrue($this->collection->isDirty());
    }

    public function testClear()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize([$foo, $bar]);
        $this->collection->clear();

        $this->assertCollectionElementsEquals([]);
        self::assertTrue($this->collection->isDirty());
    }

    public function testRemove()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize([$foo, $bar]);
        $this->collection->remove(3);

        $this->assertCollectionElementsEquals([$foo, $bar]);
        self::assertFalse($this->collection->isDirty());

        $this->collection->remove(0);
        $this->assertCollectionElementsEquals([1 => $bar]);
        self::assertTrue($this->collection->isDirty());
    }

    public function testRemoveElement()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize([$foo, $bar]);
        $this->collection->removeElement($baz);

        $this->assertCollectionElementsEquals([$foo, $bar]);
        self::assertFalse($this->collection->isDirty());

        $this->collection->removeElement($foo);
        $this->assertCollectionElementsEquals([1 => $bar]);
        self::assertTrue($this->collection->isDirty());
    }

    public function testSet()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize([$foo]);
        $this->collection->set(0, $bar);
        $this->assertCollectionElementsEquals([$bar]);
        self::assertTrue($this->collection->isDirty());

        $this->collection->set(null, $baz);
        $this->assertCollectionElementsEquals([$bar, $baz]);
        self::assertTrue($this->collection->isDirty());
    }

    public function testGetSnapshot()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        self::assertEquals([], $this->collection->getSnapshot());
        $this->expectInitialize([$foo, $bar]);

        $this->collection->isEmpty();
        self::assertEquals([$foo, $bar], $this->collection->getSnapshot());
    }

    public function testOffsetExists()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize([$foo, $bar]);
        self::assertTrue(isset($this->collection[0]));
        self::assertTrue(isset($this->collection[1]));
        self::assertFalse(isset($this->collection[2]));
        self::assertFalse($this->collection->isDirty());
    }

    public function testOffsetGet()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize([$foo, $bar]);
        self::assertEquals($foo, $this->collection[0]);
        self::assertEquals($bar, $this->collection[1]);
        self::assertNull($this->collection[2]);
        self::assertFalse($this->collection->isDirty());
    }

    public function testOffsetSet()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize([$foo, $bar]);
        $this->collection[0] = $baz;
        self::assertEquals($baz, $this->collection[0]);
        $this->assertCollectionElementsEquals([$baz, $bar]);
        self::assertTrue($this->collection->isDirty());
    }

    public function testOffsetUnset()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);

        $this->expectInitialize([$foo, $bar]);
        unset($this->collection[0]);
        $this->assertCollectionElementsEquals([1 => $bar]);
        self::assertTrue($this->collection->isDirty());
    }

    public function testGetDeleteDiff()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize([$foo, $bar, $baz]);

        self::assertEquals([], $this->collection->getDeleteDiff());

        $this->collection->removeElement($bar);
        $this->collection->removeElement($baz);

        self::assertEquals([1 => $bar, $baz], $this->collection->getDeleteDiff());
    }

    public function testGetInsertDiff()
    {
        $foo = $this->createReminder(100);
        $bar = $this->createReminder(200);
        $baz = $this->createReminder(300);

        $this->expectInitialize([$foo]);

        self::assertEquals([], $this->collection->getInsertDiff());

        $this->collection->add($bar);
        $this->collection->add($baz);

        self::assertEquals([1 => $bar, $baz], $this->collection->getInsertDiff());
    }

    private function expectInitialize(array $reminders): void
    {
        $this->repository->expects($this->once())
            ->method('findRemindersByEntity')
            ->with(self::CLASS_NAME, self::IDENTIFIER)
            ->willReturn($reminders);
    }

    private function assertCollectionElementsEquals(array $elements): void
    {
        self::assertEquals(
            new ArrayCollection($elements),
            ReflectionUtil::getPropertyValue($this->collection, 'collection')
        );
    }

    private function createReminder(int $id): Reminder
    {
        $result = $this->createMock(Reminder::class);
        $result->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $result;
    }
}
