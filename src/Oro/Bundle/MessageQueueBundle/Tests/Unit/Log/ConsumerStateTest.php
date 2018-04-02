<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log;

use Oro\Bundle\MessageQueueBundle\Log\ConsumerState;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\MessageInterface;

class ConsumerStateTest extends \PHPUnit_Framework_TestCase
{
    public function testInitialState()
    {
        $consumerState = new ConsumerState();

        $this->assertFalse($consumerState->isConsumptionStarted());
        $this->assertNull($consumerState->getExtension());
        $this->assertNull($consumerState->getMessageProcessor());
        $this->assertNull($consumerState->getMessage());
        $this->assertNull($consumerState->getJob());
    }

    public function testStartAndStopConsumption()
    {
        $consumerState = new ConsumerState();

        $consumerState->startConsumption();
        $this->assertTrue($consumerState->isConsumptionStarted());

        $consumerState->stopConsumption();
        $this->assertFalse($consumerState->isConsumptionStarted());
    }

    public function testSetExtension()
    {
        $consumerState = new ConsumerState();

        $extension = $this->createMock(ExtensionInterface::class);
        $consumerState->setExtension($extension);

        $this->assertSame($extension, $consumerState->getExtension());

        $consumerState->setExtension();

        $this->assertNull($consumerState->getExtension());
    }

    public function testSetMessageProcessor()
    {
        $consumerState = new ConsumerState();

        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $consumerState->setMessageProcessor($messageProcessor);

        $this->assertSame($messageProcessor, $consumerState->getMessageProcessor());

        $consumerState->setMessageProcessor();

        $this->assertNull($consumerState->getMessageProcessor());
    }

    public function testSetMessage()
    {
        $consumerState = new ConsumerState();

        $message = $this->createMock(MessageInterface::class);
        $consumerState->setMessage($message);

        $this->assertSame($message, $consumerState->getMessage());

        $consumerState->setMessage();

        $this->assertNull($consumerState->getMessage());
    }

    public function testSetJob()
    {
        $consumerState = new ConsumerState();

        $job = $this->createMock(Job::class);
        $consumerState->setJob($job);

        $this->assertSame($job, $consumerState->getJob());

        $consumerState->setJob();

        $this->assertNull($consumerState->getJob());
    }

    public function testClear()
    {
        $consumerState = new ConsumerState();
        $consumerState->setExtension($this->createMock(ExtensionInterface::class));
        $consumerState->setMessageProcessor($this->createMock(MessageProcessorInterface::class));
        $consumerState->setMessage($this->createMock(MessageInterface::class));
        $consumerState->setJob($this->createMock(Job::class));

        $consumerState->clear();

        $this->assertNull($consumerState->getExtension());
        $this->assertNull($consumerState->getMessageProcessor());
        $this->assertNull($consumerState->getMessage());
        $this->assertNull($consumerState->getJob());
    }
}
