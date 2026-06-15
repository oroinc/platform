<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\NotifiableQueueIteratorRegistryInterface;
use Oro\Component\MessageQueue\Consumption\QueueIterator\SequentialExhaustiveQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\SequentialExhaustiveQueueIteratorFactory;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SequentialExhaustiveQueueIteratorFactoryTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private NotifiableQueueIteratorRegistryInterface&MockObject $queueIteratorRegistry;
    private SequentialExhaustiveQueueIteratorFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->queueIteratorRegistry = $this->createMock(NotifiableQueueIteratorRegistryInterface::class);
        $this->factory = new SequentialExhaustiveQueueIteratorFactory($this->queueIteratorRegistry);
        $this->setUpLoggerMock($this->factory);
    }

    public function testCreateQueueIteratorReturnsSequentialExhaustiveQueueIteratorInstance(): void
    {
        $this->queueIteratorRegistry->expects(self::once())
            ->method('addQueueIterator');

        $boundQueues = ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']];
        $result = $this->factory->createQueueIterator(
            $boundQueues,
            SequentialExhaustiveQueueIterator::NAME
        );

        self::assertInstanceOf(SequentialExhaustiveQueueIterator::class, $result);
        self::assertEquals($boundQueues, iterator_to_array($result));
    }

    public function testCreateQueueIteratorRegistersIteratorWithRegistry(): void
    {
        $this->queueIteratorRegistry->expects(self::once())
            ->method('addQueueIterator')
            ->with(self::isInstanceOf(SequentialExhaustiveQueueIterator::class));

        $this->factory->createQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']],
            SequentialExhaustiveQueueIterator::NAME
        );
    }

    public function testCreateQueueIteratorForwardsLoggerToIterator(): void
    {
        $this->queueIteratorRegistry->expects(self::any())
            ->method('addQueueIterator');

        $result = $this->factory->createQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']],
            SequentialExhaustiveQueueIterator::NAME
        );

        self::assertInstanceOf(SequentialExhaustiveQueueIterator::class, $result);
        self::assertSame($this->loggerMock, ReflectionUtil::getPropertyValue($result, 'logger'));
    }
}
