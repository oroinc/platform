<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\ChainQueueIteratorFactory;
use Oro\Component\MessageQueue\Consumption\QueueIterator\DefaultQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\QueueIteratorFactoryInterface;
use Oro\Component\MessageQueue\Consumption\QueueIterator\QueueIteratorFactoryRegistry;
use Oro\Component\MessageQueue\Consumption\QueueIterator\StrictPriorityInterleavingQueueIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ChainQueueIteratorFactoryTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private QueueIteratorFactoryRegistry&MockObject $registry;
    private ChainQueueIteratorFactory $chain;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(QueueIteratorFactoryRegistry::class);
        $this->chain = new ChainQueueIteratorFactory($this->registry);
        $this->setUpLoggerMock($this->chain);
    }

    public function testCreateQueueIteratorDelegatesToRegistry(): void
    {
        $boundQueues = ['q1' => [QueueConsumer::PROCESSOR => 'proc1']];
        $consumptionMode = DefaultQueueIterator::NAME;
        $expectedIterator = new \ArrayIterator([]);

        $factory = $this->createMock(QueueIteratorFactoryInterface::class);
        $factory->expects(self::once())
            ->method('createQueueIterator')
            ->with($boundQueues, $consumptionMode)
            ->willReturn($expectedIterator);

        $this->registry->expects(self::once())
            ->method('getQueueIteratorFactory')
            ->with($consumptionMode)
            ->willReturn($factory);

        $result = $this->chain->createQueueIterator($boundQueues, $consumptionMode);

        self::assertSame($expectedIterator, $result);
    }

    public function testCreateQueueIteratorPropagatesLogicExceptionFromRegistry(): void
    {
        $this->registry->expects(self::once())
            ->method('getQueueIteratorFactory')
            ->with('unknown-mode')
            ->willThrowException(new \LogicException('No factory for mode "unknown-mode".'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No factory for mode "unknown-mode".');

        $this->chain->createQueueIterator([], 'unknown-mode');
    }

    public function testCreateQueueIteratorLogsInfoWithCorrectMessageAndContext(): void
    {
        $boundQueues = [
            'queue-a' => [QueueConsumer::PROCESSOR => 'proc1'],
            'queue-b' => [QueueConsumer::PROCESSOR => 'proc2']
        ];
        $consumptionMode = StrictPriorityInterleavingQueueIterator::NAME;
        $expectedIterator = new \ArrayIterator([]);

        $factory = $this->createMock(QueueIteratorFactoryInterface::class);
        $factory->expects(self::once())
            ->method('createQueueIterator')
            ->willReturn($expectedIterator);

        $this->registry->expects(self::once())
            ->method('getQueueIteratorFactory')
            ->willReturn($factory);

        $this->loggerMock->expects(self::once())
            ->method('info')
            ->with(
                'Creating a queue iterator in "{consumptionMode}" mode for queues: {queues}',
                [
                    'consumptionMode' => $consumptionMode,
                    'queues' => 'queue-a, queue-b',
                    'queuesSettings' => $boundQueues,
                ]
            );

        $this->chain->createQueueIterator($boundQueues, $consumptionMode);
    }
}
