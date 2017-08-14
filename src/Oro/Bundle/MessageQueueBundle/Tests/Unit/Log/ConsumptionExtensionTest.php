<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\ConsumptionExtension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

use Oro\Bundle\MessageQueueBundle\Log\ConsumptionExtension;
use Oro\Bundle\MessageQueueBundle\Log\ConsumerState;

class ConsumptionExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testOnBeforeReceive()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $message = new DbalMessage();
        $job = new Job();

        $context = new Context(self::createMock(SessionInterface::class));
        $context->setMessageProcessor($messageProcessor);
        $context->setMessage($message);

        $consumerState = new ConsumerState();
        $consumerState->setMessageProcessor($messageProcessor);
        $consumerState->setMessage($message);
        $consumerState->setJob($job);

        $extension = new ConsumptionExtension($consumerState);
        $extension->onBeforeReceive($context);

        $this->assertNull($consumerState->getMessageProcessor());
        $this->assertNull($consumerState->getMessage());
        $this->assertNull($consumerState->getJob());
    }

    public function testOnPreReceived()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $message = new DbalMessage();

        $context = new Context(self::createMock(SessionInterface::class));
        $context->setMessageProcessor($messageProcessor);
        $context->setMessage($message);

        $consumerState = new ConsumerState();

        $extension = new ConsumptionExtension($consumerState);
        $extension->onPreReceived($context);

        $this->assertSame($messageProcessor, $consumerState->getMessageProcessor());
        $this->assertSame($message, $consumerState->getMessage());
    }

    public function testOnPostReceived()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $message = new DbalMessage();

        $context = new Context(self::createMock(SessionInterface::class));
        $context->setMessageProcessor($messageProcessor);
        $context->setMessage($message);

        $consumerState = new ConsumerState();
        $consumerState->setMessageProcessor($messageProcessor);
        $consumerState->setMessage($message);

        $extension = new ConsumptionExtension($consumerState);
        $extension->onPostReceived($context);

        $this->assertNull($consumerState->getMessageProcessor());
        $this->assertNull($consumerState->getMessage());
    }
}
