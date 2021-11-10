<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\ConsumptionExtension;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testOnBeforeReceive(): void
    {
        $messageProcessorName = 'sample_processor';
        $message = new Message();
        $job = new Job();

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessorName($messageProcessorName);
        $context->setMessage($message);

        $consumerState = new ConsumerState();
        $consumerState->setMessageProcessorName($messageProcessorName);
        $consumerState->setMessage($message);
        $consumerState->setJob($job);

        $extension = new ConsumptionExtension($consumerState);
        $extension->onBeforeReceive($context);

        self::assertSame('', $consumerState->getMessageProcessorName());
        self::assertNull($consumerState->getMessage());
        self::assertNull($consumerState->getJob());
    }

    public function testOnPreReceived(): void
    {
        $messageProcessorName = 'sample_processor';
        $message = new Message();

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessorName($messageProcessorName);
        $context->setMessage($message);

        $consumerState = new ConsumerState();

        $extension = new ConsumptionExtension($consumerState);
        $extension->onPreReceived($context);

        self::assertSame($messageProcessorName, $consumerState->getMessageProcessorName());
        self::assertSame($message, $consumerState->getMessage());
    }

    public function testOnPostReceived(): void
    {
        $messageProcessorName = 'sample_processor';
        $message = new Message();

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessorName($messageProcessorName);
        $context->setMessage($message);

        $consumerState = new ConsumerState();
        $consumerState->setMessageProcessorName($messageProcessorName);
        $consumerState->setMessage($message);

        $extension = new ConsumptionExtension($consumerState);
        $extension->onPostReceived($context);

        self::assertSame('', $consumerState->getMessageProcessorName());
        self::assertNull($consumerState->getMessage());
    }
}
