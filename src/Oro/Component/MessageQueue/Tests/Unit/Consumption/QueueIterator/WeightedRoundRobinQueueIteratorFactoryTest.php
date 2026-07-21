<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\NotifiableQueueIteratorRegistryInterface;
use Oro\Component\MessageQueue\Consumption\QueueIterator\WeightedRoundRobinQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\WeightedRoundRobinQueueIteratorFactory;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class WeightedRoundRobinQueueIteratorFactoryTest extends TestCase
{
    private NotifiableQueueIteratorRegistryInterface&MockObject $queueIteratorRegistry;

    private WeightedRoundRobinQueueIteratorFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->queueIteratorRegistry = $this->createMock(NotifiableQueueIteratorRegistryInterface::class);

        $this->factory = new WeightedRoundRobinQueueIteratorFactory(
            $this->queueIteratorRegistry
        );
    }

    public function testCreateQueueIteratorReturnsWeightedRoundRobinQueueIteratorInstance(): void
    {
        $this->queueIteratorRegistry
            ->expects(self::once())
            ->method('addQueueIterator');

        $boundQueues = ['q1' => [QueueConsumer::PROCESSOR => 'p1', 'weight' => '2']];
        $result = $this->factory->createQueueIterator(
            $boundQueues,
            WeightedRoundRobinQueueIterator::NAME
        );

        self::assertInstanceOf(WeightedRoundRobinQueueIterator::class, $result);
        // Verify the iterator was constructed with the correct bound-queue data without driving it.
        self::assertSame(array_keys($boundQueues), ReflectionUtil::getPropertyValue($result, 'keys'));
        self::assertSame(array_values($boundQueues), ReflectionUtil::getPropertyValue($result, 'values'));
    }

    public function testCreateQueueIteratorCallsAddQueueIteratorOnRegistryWithCreatedIterator(): void
    {
        $this->queueIteratorRegistry
            ->expects(self::once())
            ->method('addQueueIterator')
            ->with(self::isInstanceOf(WeightedRoundRobinQueueIterator::class));

        $this->factory->createQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'p1', 'weight' => '2']],
            WeightedRoundRobinQueueIterator::NAME
        );
    }

    public function testCreateQueueIteratorForwardsLoggerToIterator(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $this->queueIteratorRegistry
            ->expects(self::once())
            ->method('addQueueIterator');

        $this->factory->setLogger($logger);

        $result = $this->factory->createQueueIterator(
            ['q1' => ['processor' => 'p1', 'weight' => '2']],
            WeightedRoundRobinQueueIterator::NAME
        );

        self::assertSame($logger, ReflectionUtil::getPropertyValue($result, 'logger'));
    }

    public function testCreateQueueIteratorReturnsNewDistinctInstanceOnEachCall(): void
    {
        $this->queueIteratorRegistry
            ->expects(self::exactly(2))
            ->method('addQueueIterator');

        $boundQueues = ['q1' => ['processor' => 'p1', 'weight' => '2']];

        $firstResult = $this->factory->createQueueIterator($boundQueues, WeightedRoundRobinQueueIterator::NAME);
        $secondResult = $this->factory->createQueueIterator($boundQueues, WeightedRoundRobinQueueIterator::NAME);

        self::assertNotSame($firstResult, $secondResult);
    }
}
