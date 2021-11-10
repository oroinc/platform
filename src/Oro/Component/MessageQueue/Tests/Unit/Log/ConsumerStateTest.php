<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Transport\MessageInterface;

class ConsumerStateTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialState(): void
    {
        $consumerState = new ConsumerState();

        self::assertFalse($consumerState->isConsumptionStarted());
        self::assertNull($consumerState->getExtension());
        self::assertSame('', $consumerState->getMessageProcessorName());
        self::assertNull($consumerState->getMessage());
        self::assertNull($consumerState->getJob());
        self::assertNull($consumerState->getStartTime());
        self::assertNull($consumerState->getStartMemoryUsage());
        self::assertNull($consumerState->getPeakMemory());
    }

    public function testStartAndStopConsumption(): void
    {
        $consumerState = new ConsumerState();

        $consumerState->startConsumption();
        self::assertTrue($consumerState->isConsumptionStarted());

        $consumerState->stopConsumption();
        self::assertFalse($consumerState->isConsumptionStarted());
    }

    public function testSetExtension(): void
    {
        $consumerState = new ConsumerState();

        $extension = $this->createMock(ExtensionInterface::class);
        $consumerState->setExtension($extension);

        self::assertSame($extension, $consumerState->getExtension());

        $consumerState->setExtension();

        self::assertNull($consumerState->getExtension());
    }

    public function testSetMessageProcessorName(): void
    {
        $consumerState = new ConsumerState();

        $messageProcessorName = 'sample_processor';
        $consumerState->setMessageProcessorName($messageProcessorName);

        self::assertSame($messageProcessorName, $consumerState->getMessageProcessorName());

        $consumerState->setMessageProcessorName();

        self::assertSame('', $consumerState->getMessageProcessorName());
    }

    public function testSetMessage(): void
    {
        $consumerState = new ConsumerState();

        $message = $this->createMock(MessageInterface::class);
        $consumerState->setMessage($message);

        self::assertSame($message, $consumerState->getMessage());

        $time = (int)(microtime(true) * 1000);
        $consumerState->setMessage();

        self::assertNull($consumerState->getMessage());

        self::assertGreaterThanOrEqual($time, $consumerState->getStartTime());
        self::assertIsInt($consumerState->getStartMemoryUsage());
        self::assertEquals($consumerState->getStartMemoryUsage(), $consumerState->getPeakMemory());
    }

    public function testSetJob(): void
    {
        $consumerState = new ConsumerState();

        $job = $this->createMock(Job::class);
        $consumerState->setJob($job);

        self::assertSame($job, $consumerState->getJob());

        $consumerState->setJob();

        self::assertNull($consumerState->getJob());
    }

    public function testClear(): void
    {
        $consumerState = new ConsumerState();
        $consumerState->setExtension($this->createMock(ExtensionInterface::class));
        $consumerState->setMessageProcessorName('sample_processor');
        $consumerState->setMessage($this->createMock(MessageInterface::class));
        $consumerState->setJob($this->createMock(Job::class));

        $consumerState->clear();

        self::assertNull($consumerState->getExtension());
        self::assertSame('', $consumerState->getMessageProcessorName());
        self::assertNull($consumerState->getMessage());
        self::assertNull($consumerState->getJob());
        self::assertNull($consumerState->getStartTime());
        self::assertEquals(0, $consumerState->getStartMemoryUsage());
        self::assertEquals(0, $consumerState->getPeakMemory());
    }

    public function testSetPeakMemory(): void
    {
        $consumerState = new ConsumerState();

        self::assertNull($consumerState->getPeakMemory());
        $peakMemory = 5;
        $consumerState->setPeakMemory($peakMemory);
        self::assertEquals($peakMemory, $consumerState->getPeakMemory());
        $consumerState->setPeakMemory(4); // lower value
        self::assertEquals($peakMemory, $consumerState->getPeakMemory()); // value remains the same
    }
}
