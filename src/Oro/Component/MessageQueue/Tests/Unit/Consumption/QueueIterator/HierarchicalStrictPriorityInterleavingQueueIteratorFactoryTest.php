<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\HierarchicalStrictPriorityInterleavingQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\HierarchicalStrictPriorityInterleavingQueueIteratorFactory;
use Oro\Component\MessageQueue\Consumption\QueueIterator\NotifiableQueueIteratorRegistryInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class HierarchicalStrictPriorityInterleavingQueueIteratorFactoryTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private NotifiableQueueIteratorRegistryInterface&MockObject $queueIteratorRegistry;
    private HierarchicalStrictPriorityInterleavingQueueIteratorFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->queueIteratorRegistry = $this->createMock(NotifiableQueueIteratorRegistryInterface::class);
        $this->factory = new HierarchicalStrictPriorityInterleavingQueueIteratorFactory($this->queueIteratorRegistry);
        $this->setUpLoggerMock($this->factory);
    }

    public function testCreateQueueIteratorReturnsHierarchicalStrictPriorityInterleavingQueueIteratorInstance(): void
    {
        $this->queueIteratorRegistry->expects(self::once())
            ->method('addQueueIterator');

        $boundQueues = ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']];
        $result = $this->factory->createQueueIterator(
            $boundQueues,
            HierarchicalStrictPriorityInterleavingQueueIterator::NAME
        );

        self::assertInstanceOf(HierarchicalStrictPriorityInterleavingQueueIterator::class, $result);
        self::assertEquals($boundQueues, iterator_to_array($result));
    }

    public function testCreateQueueIteratorRegistersIteratorWithRegistry(): void
    {
        $this->queueIteratorRegistry->expects(self::once())
            ->method('addQueueIterator')
            ->with(self::isInstanceOf(HierarchicalStrictPriorityInterleavingQueueIterator::class));

        $this->factory->createQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']],
            HierarchicalStrictPriorityInterleavingQueueIterator::NAME
        );
    }

    public function testCreateQueueIteratorForwardsLoggerToIterator(): void
    {
        $this->queueIteratorRegistry->expects(self::any())
            ->method('addQueueIterator');

        $result = $this->factory->createQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']],
            HierarchicalStrictPriorityInterleavingQueueIterator::NAME
        );

        self::assertInstanceOf(HierarchicalStrictPriorityInterleavingQueueIterator::class, $result);
        self::assertSame($this->loggerMock, ReflectionUtil::getPropertyValue($result, 'logger'));
    }
}
