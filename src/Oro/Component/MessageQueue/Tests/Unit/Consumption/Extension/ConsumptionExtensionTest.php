<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\ConsumptionExtension;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Log\MessageProcessorClassProvider;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    private MessageProcessorClassProvider|\PHPUnit\Framework\MockObject\MockObject $messageProcessorClassProvider;

    protected function setUp(): void
    {
        $this->messageProcessorClassProvider = $this->createMock(MessageProcessorClassProvider::class);

        $this->messageProcessorClassProvider
            ->expects(self::any())
            ->method('getMessageProcessorClassByName')
            ->willReturnCallback(static fn (string $name) => str_replace('_', '', ucwords($name, '_')) . 'Class');
    }

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
        $consumerState->setMessageProcessorClass(\stdClass::class);
        $consumerState->setMessage($message);
        $consumerState->setJob($job);

        $extension = new ConsumptionExtension($consumerState, $this->messageProcessorClassProvider);
        $extension->onBeforeReceive($context);

        self::assertSame('', $consumerState->getMessageProcessorName());
        self::assertSame('', $consumerState->getMessageProcessorClass());
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

        $extension = new ConsumptionExtension($consumerState, $this->messageProcessorClassProvider);
        $extension->onPreReceived($context);

        self::assertSame($messageProcessorName, $consumerState->getMessageProcessorName());
        self::assertSame('SampleProcessorClass', $consumerState->getMessageProcessorClass());
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
        $consumerState->setMessageProcessorClass(\stdClass::class);
        $consumerState->setMessage($message);

        $extension = new ConsumptionExtension($consumerState, $this->messageProcessorClassProvider);
        $extension->onPostReceived($context);

        self::assertSame('', $consumerState->getMessageProcessorName());
        self::assertSame('', $consumerState->getMessageProcessorClass());
        self::assertNull($consumerState->getMessage());
    }
}
