<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport;

use Oro\Component\MessageQueue\Transport\QueueCollection;
use Oro\Component\MessageQueue\Transport\QueueInterface;

class QueueCollectionTest extends \PHPUnit_Framework_TestCase
{
    /** @var QueueCollection */
    private $collection;

    protected function setUp()
    {
        $this->collection = new QueueCollection();
    }

    public function testHasShouldReturnFalseIfQueueIsNotAdded()
    {
        self::assertFalse($this->collection->has('queue'));
    }

    public function testHasShouldReturnFalseIfQueueIsAdded()
    {
        $this->collection->set('queue', $this->createMock(QueueInterface::class));
        self::assertTrue($this->collection->has('queue'));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testGetShouldThrowExceptionIfQueueIsNotAdded()
    {
        $this->collection->get('queue');
    }

    public function testGetShouldReturnAddedQueue()
    {
        $queue = $this->createMock(QueueInterface::class);
        $this->collection->set('queue', $queue);
        self::assertSame($queue, $this->collection->get('queue'));
    }

    public function testSetShouldAddQueueToCollection()
    {
        $queue1 = $this->createMock(QueueInterface::class);
        $queue2 = $this->createMock(QueueInterface::class);
        $this->collection->set('queue1', $queue1);
        $this->collection->set('queue2', $queue2);

        self::assertSame($queue1, $this->collection->get('queue1'));
        self::assertSame($queue2, $this->collection->get('queue2'));
    }

    public function testSetShouldReplaceExistingQueue()
    {
        $queue1 = $this->createMock(QueueInterface::class);
        $queue2 = $this->createMock(QueueInterface::class);

        $this->collection->set('queue1', $queue1);
        self::assertSame($queue1, $this->collection->get('queue1'));

        $this->collection->set('queue1', $queue2);
        self::assertSame($queue2, $this->collection->get('queue1'));
    }

    public function testRemoveShouldReturnNullIfQueueDoesNotExist()
    {
        self::assertNull($this->collection->remove('queue'));
    }

    public function testRemoveShouldReturnRemovedQueue()
    {
        $queue = $this->createMock(QueueInterface::class);
        $this->collection->set('queue', $queue);

        $removedQueue = $this->collection->remove('queue');
        self::assertFalse($this->collection->has('queue'));
        self::assertSame($queue, $removedQueue);
    }

    public function testClearShouldRemoveAllQueues()
    {
        $this->collection->set('queue1', $this->createMock(QueueInterface::class));
        $this->collection->set('queue2', $this->createMock(QueueInterface::class));

        $this->collection->clear();
        self::assertFalse($this->collection->has('queue1'));
        self::assertFalse($this->collection->has('queue2'));
    }

    public function testIsEmptyShouldReturnTrueIfNoQueuesInCollection()
    {
        self::assertTrue($this->collection->isEmpty());
    }

    public function testIsEmptyShouldReturnFalseIfAtLeastOneQueueExistsInCollection()
    {
        $this->collection->set('queue', $this->createMock(QueueInterface::class));
        self::assertFalse($this->collection->isEmpty());
    }

    public function testAllShouldReturnAllQueues()
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
