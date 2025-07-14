<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport;

use Oro\Component\MessageQueue\Transport\QueueCollection;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QueueCollectionTest extends TestCase
{
    private QueueCollection $collection;

    #[\Override]
    protected function setUp(): void
    {
        $this->collection = new QueueCollection();
    }

    public function testHasShouldReturnFalseIfQueueIsNotAdded(): void
    {
        self::assertFalse($this->collection->has('queue'));
    }

    public function testHasShouldReturnFalseIfQueueIsAdded(): void
    {
        $this->collection->set('queue', $this->createMock(QueueInterface::class));
        self::assertTrue($this->collection->has('queue'));
    }

    public function testGetShouldThrowExceptionIfQueueIsNotAdded(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->collection->get('queue');
    }

    public function testGetShouldReturnAddedQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $this->collection->set('queue', $queue);
        self::assertSame($queue, $this->collection->get('queue'));
    }

    public function testSetShouldAddQueueToCollection(): void
    {
        $queue1 = $this->createMock(QueueInterface::class);
        $queue2 = $this->createMock(QueueInterface::class);
        $this->collection->set('queue1', $queue1);
        $this->collection->set('queue2', $queue2);

        self::assertSame($queue1, $this->collection->get('queue1'));
        self::assertSame($queue2, $this->collection->get('queue2'));
    }

    public function testSetShouldReplaceExistingQueue(): void
    {
        $queue1 = $this->createMock(QueueInterface::class);
        $queue2 = $this->createMock(QueueInterface::class);

        $this->collection->set('queue1', $queue1);
        self::assertSame($queue1, $this->collection->get('queue1'));

        $this->collection->set('queue1', $queue2);
        self::assertSame($queue2, $this->collection->get('queue1'));
    }

    public function testRemoveShouldReturnNullIfQueueDoesNotExist(): void
    {
        self::assertNull($this->collection->remove('queue'));
    }

    public function testRemoveShouldReturnRemovedQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $this->collection->set('queue', $queue);

        $removedQueue = $this->collection->remove('queue');
        self::assertFalse($this->collection->has('queue'));
        self::assertSame($queue, $removedQueue);
    }

    public function testClearShouldRemoveAllQueues(): void
    {
        $this->collection->set('queue1', $this->createMock(QueueInterface::class));
        $this->collection->set('queue2', $this->createMock(QueueInterface::class));

        $this->collection->clear();
        self::assertFalse($this->collection->has('queue1'));
        self::assertFalse($this->collection->has('queue2'));
    }

    public function testIsEmptyShouldReturnTrueIfNoQueuesInCollection(): void
    {
        self::assertTrue($this->collection->isEmpty());
    }

    public function testIsEmptyShouldReturnFalseIfAtLeastOneQueueExistsInCollection(): void
    {
        $this->collection->set('queue', $this->createMock(QueueInterface::class));
        self::assertFalse($this->collection->isEmpty());
    }

    public function testAllShouldReturnAllQueues(): void
    {
        $queue1 = $this->createMock(QueueInterface::class);
        $queue2 = $this->createMock(QueueInterface::class);
        $this->collection->set('queue1', $queue1);
        $this->collection->set('queue2', $queue2);

        self::assertEquals(
            ['queue1' => $queue1, 'queue2' => $queue2],
            $this->collection->all()
        );
    }
}
