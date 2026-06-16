<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\DefaultQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\QueueIteratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class DefaultQueueIteratorTest extends TestCase
{
    private LoggerInterface&MockObject $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testImplementsQueueIteratorInterface(): void
    {
        $iterator = new DefaultQueueIterator([]);
        self::assertInstanceOf(QueueIteratorInterface::class, $iterator);
    }

    public function testIteratesAllQueuesInOrder(): void
    {
        $iterator = new DefaultQueueIterator(
            [
                'q1' => [QueueConsumer::PROCESSOR => 'proc1'],
                'q2' => [QueueConsumer::PROCESSOR => 'proc2'],
                'q3' => [QueueConsumer::PROCESSOR => 'proc3']
            ]
        );
        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
        self::assertSame([QueueConsumer::PROCESSOR => 'proc1'], $iterator->current());

        $iterator->next();

        self::assertTrue($iterator->valid());
        self::assertSame('q2', $iterator->key());
        self::assertSame([QueueConsumer::PROCESSOR => 'proc2'], $iterator->current());

        $iterator->next();

        self::assertTrue($iterator->valid());
        self::assertSame('q3', $iterator->key());
        self::assertSame([QueueConsumer::PROCESSOR => 'proc3'], $iterator->current());

        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testRewindStartsFromBeginning(): void
    {
        $iterator = new DefaultQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']]
        );
        $iterator->rewind();
        $iterator->next();
        $iterator->next();

        self::assertFalse($iterator->valid());

        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
        self::assertSame([QueueConsumer::PROCESSOR => 'proc1'], $iterator->current());

        $iterator->next();

        self::assertTrue($iterator->valid());
        self::assertSame('q2', $iterator->key());
        self::assertSame([QueueConsumer::PROCESSOR => 'proc2'], $iterator->current());

        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testValidReturnsFalseAfterLastElement(): void
    {
        $iterator = new DefaultQueueIterator(['q1' => [QueueConsumer::PROCESSOR => 'proc1']]);
        $iterator->rewind();

        self::assertTrue($iterator->valid());

        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testEmptyBoundQueues(): void
    {
        $iterator = new DefaultQueueIterator([]);
        $iterator->rewind();

        self::assertFalse($iterator->valid());
    }

    public function testSingleQueue(): void
    {
        $iterator = new DefaultQueueIterator(['only-queue' => ['processor' => 'only-proc']]);
        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('only-queue', $iterator->key());
        self::assertSame(['processor' => 'only-proc'], $iterator->current());

        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testKeyAndCurrentReturnCorrectValues(): void
    {
        $iterator = new DefaultQueueIterator(
            ['q1' => ['processor' => 'proc1'], 'q2' => ['processor' => 'proc2'], 'q3' => ['processor' => 'proc3']]
        );

        $iterator->rewind();
        $iterator->next();

        self::assertSame('q2', $iterator->key());
        self::assertSame(['processor' => 'proc2'], $iterator->current());
    }

    public function testNextLogsDebugWhenSwitchingToNextQueue(): void
    {
        $iterator = new DefaultQueueIterator(
            ['q1' => ['processor' => 'proc1'], 'q2' => ['processor' => 'proc2'], 'q3' => ['processor' => 'proc3']]
        );
        $iterator->setLogger($this->logger);
        $iterator->rewind();

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(
                'Switching to queue "{queue}".',
                ['queue' => 'q2']
            );

        $iterator->next();
    }

    public function testNextLogsDebugWhenDefaultCycleComplete(): void
    {
        $iterator = new DefaultQueueIterator(['q1' => ['processor' => 'proc1'], 'q2' => ['processor' => 'proc2']]);
        $iterator->setLogger($this->logger);
        $iterator->rewind();
        $iterator->next();

        $this->logger->expects(self::once())
            ->method('debug')
            ->with('All queues visited - default cycle complete.');

        $iterator->next();
    }

    public function testNextDoesNotLogWhenSingleQueueAndDone(): void
    {
        $iterator = new DefaultQueueIterator(['only-queue' => ['processor' => 'only-proc']]);
        $iterator->setLogger($this->logger);
        $iterator->rewind();

        $this->logger->expects(self::never())
            ->method('debug');

        $iterator->next();
    }

    public function testRewindLogsDebugWhenStartingNewCycle(): void
    {
        $iterator = new DefaultQueueIterator(
            ['q1' => ['processor' => 'proc1'], 'q2' => ['processor' => 'proc2']]
        );
        $iterator->setLogger($this->logger);

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(
                'Starting a new default cycle; first queue: "{queue}".',
                ['queue' => 'q1']
            );

        $iterator->rewind();
    }
}
