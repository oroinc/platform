<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\DBAL\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RejectMessageOnExceptionDbalExtension;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class RejectMessageOnExceptionDbalExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldDoNothingIfExceptionIsMissing()
    {
        $consumer = $this->createMock(MessageConsumerInterface::class);
        $consumer->expects($this->never())
            ->method('reject');

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageConsumer($consumer);

        $extension = new RejectMessageOnExceptionDbalExtension();
        $extension->onInterrupted($context);
    }

    public function testShouldDoNothingIfMessageIsMissing()
    {
        $consumer = $this->createMock(MessageConsumerInterface::class);
        $consumer->expects($this->never())
            ->method('reject');

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setException(new \Exception());
        $context->setMessageConsumer($consumer);

        $extension = new RejectMessageOnExceptionDbalExtension();
        $extension->onInterrupted($context);
    }

    public function testShouldRejectMessage()
    {
        $message = new Message();
        $message->setMessageId(123);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with(
                'Execution was interrupted and message was rejected. {id}',
                ['id' => '123']
            );

        $consumer = $this->createMock(MessageConsumerInterface::class);
        $consumer->expects($this->once())
            ->method('reject')
            ->with($this->identicalTo($message), $this->isTrue());

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);
        $context->setException(new \Exception());
        $context->setMessage($message);
        $context->setMessageConsumer($consumer);

        $extension = new RejectMessageOnExceptionDbalExtension();
        $extension->onInterrupted($context);
    }
}
