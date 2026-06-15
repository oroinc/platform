<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\NotifiableQueueIteratorRegistryInterface;
use Oro\Component\MessageQueue\Consumption\QueueIterator\StrictPriorityInterleavingQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\StrictPriorityInterleavingQueueIteratorFactory;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class StrictPriorityInterleavingQueueIteratorFactoryTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private NotifiableQueueIteratorRegistryInterface&MockObject $notifiableQueueIteratorRegistry;
    private StrictPriorityInterleavingQueueIteratorFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->notifiableQueueIteratorRegistry = $this->createMock(NotifiableQueueIteratorRegistryInterface::class);
        $this->factory = new StrictPriorityInterleavingQueueIteratorFactory($this->notifiableQueueIteratorRegistry);
        $this->setUpLoggerMock($this->factory);
    }

    public function testCreateQueueIteratorReturnsStrictPriorityIteratorInstance(): void
    {
        $this->notifiableQueueIteratorRegistry->expects(self::once())
            ->method('addQueueIterator');

        $boundQueues = ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']];
        $result = $this->factory->createQueueIterator(
            $boundQueues,
            StrictPriorityInterleavingQueueIterator::NAME
        );

        self::assertInstanceOf(StrictPriorityInterleavingQueueIterator::class, $result);
        self::assertEquals($boundQueues, iterator_to_array($result));
    }

    public function testCreateQueueIteratorRegistersIteratorWithRegistry(): void
    {
        $this->notifiableQueueIteratorRegistry->expects(self::once())
            ->method('addQueueIterator')
            ->with(self::isInstanceOf(StrictPriorityInterleavingQueueIterator::class));

        $this->factory->createQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']],
            StrictPriorityInterleavingQueueIterator::NAME
        );
    }

    public function testCreateQueueIteratorForwardsLoggerToIterator(): void
    {
        $this->notifiableQueueIteratorRegistry->expects(self::any())
            ->method('addQueueIterator');

        $result = $this->factory->createQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']],
            StrictPriorityInterleavingQueueIterator::NAME
        );

        self::assertInstanceOf(StrictPriorityInterleavingQueueIterator::class, $result);
        self::assertSame($this->loggerMock, ReflectionUtil::getPropertyValue($result, 'logger'));
    }
}
