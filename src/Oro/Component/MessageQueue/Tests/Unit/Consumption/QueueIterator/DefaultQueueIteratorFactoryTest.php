<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\DefaultQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\DefaultQueueIteratorFactory;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

final class DefaultQueueIteratorFactoryTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private DefaultQueueIteratorFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new DefaultQueueIteratorFactory();
        $this->setUpLoggerMock($this->factory);
    }

    public function testCreateQueueIteratorReturnsDefaultIteratorInstance(): void
    {
        $boundQueues = ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']];
        $result = $this->factory->createQueueIterator(
            $boundQueues,
            DefaultQueueIterator::NAME
        );

        self::assertInstanceOf(DefaultQueueIterator::class, $result);
        self::assertEquals($boundQueues, iterator_to_array($result));
    }

    public function testCreateQueueIteratorReturnsNewInstanceEachCall(): void
    {
        $first = $this->factory->createQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1']],
            DefaultQueueIterator::NAME
        );
        $second = $this->factory->createQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1']],
            DefaultQueueIterator::NAME
        );

        self::assertNotSame($first, $second);
    }

    public function testCreateQueueIteratorForwardsLoggerToIterator(): void
    {
        $result = $this->factory->createQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1']],
            DefaultQueueIterator::NAME
        );

        self::assertInstanceOf(DefaultQueueIterator::class, $result);
        self::assertSame($this->loggerMock, ReflectionUtil::getPropertyValue($result, 'logger'));
    }
}
