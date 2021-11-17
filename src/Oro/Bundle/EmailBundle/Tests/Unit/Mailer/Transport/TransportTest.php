<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Transport;

use Oro\Bundle\EmailBundle\Event\BeforeMessageEvent;
use Oro\Bundle\EmailBundle\Mailer\Transport\Transport;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class TransportTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private TransportInterface|\PHPUnit\Framework\MockObject\MockObject $decoratedTransport;

    private Transport $transport;

    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->decoratedTransport = $this->createMock(TransportInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->transport = new Transport($this->decoratedTransport, $this->eventDispatcher);

        $this->setUpLoggerMock($this->transport);
    }

    public function testSendWithoutEnvelope(): void
    {
        $message = new RawMessage('sample body');

        $event = new BeforeMessageEvent($message, null);
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with($event)
            ->willReturnArgument(0);

        $sentMessage = $this->createMock(SentMessage::class);
        $this->decoratedTransport->expects(self::once())
            ->method('send')
            ->with($message, null)
            ->willReturn($sentMessage);

        self::assertSame($sentMessage, $this->transport->send($message, null));
    }

    public function testSendWithEnvelope(): void
    {
        $message = new RawMessage('sample body');
        $envelope = new Envelope(new SymfonyAddress('from@example.com'), [new SymfonyAddress('to@example.com')]);

        $event = new BeforeMessageEvent($message, $envelope);
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with($event)
            ->willReturnArgument(0);

        $sentMessage = $this->createMock(SentMessage::class);
        $this->decoratedTransport->expects(self::once())
            ->method('send')
            ->with($message, $envelope)
            ->willReturn($sentMessage);

        self::assertSame($sentMessage, $this->transport->send($message, $envelope));
    }

    public function testSendShouldUseEnvelopeFromEvent(): void
    {
        $message = new RawMessage('sample body');
        $envelope = new Envelope(new SymfonyAddress('from1@example.com'), [new SymfonyAddress('to1@example.com')]);

        $event = new BeforeMessageEvent($message, $envelope);
        $newEnvelope = new Envelope(new SymfonyAddress('from2@example.com'), [new SymfonyAddress('to2@example.com')]);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(static function (BeforeMessageEvent $event) use ($newEnvelope) {
                $event->setEnvelope($newEnvelope);

                return $event;
            });

        $sentMessage = $this->createMock(SentMessage::class);
        $this->decoratedTransport->expects(self::once())
            ->method('send')
            ->with($message, $newEnvelope)
            ->willReturn($sentMessage);

        self::assertSame($sentMessage, $this->transport->send($message, $envelope));
    }

    public function testSendShouldLogWhenRawMessageAndException(): void
    {
        $exception = new \RuntimeException('Sample exception message');

        $this->expectExceptionObject($exception);

        $message = new RawMessage('sample body');

        $this->decoratedTransport->expects(self::once())
            ->method('send')
            ->with($message)
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                sprintf('Failed to send a %s message: %s', get_debug_type($message), $exception->getMessage()),
                [
                    'message' => $message,
                    'exception' => $exception,
                ]
            );


        $this->transport->send($message);
    }

    public function testSendShouldLogWhenNoEnvelopeAndException(): void
    {
        $exception = new \RuntimeException('Sample exception message');

        $this->expectExceptionObject($exception);

        $emailMessageText = 'Some raw email message';
        $message = (new Email())
            ->from('from@example.com')
            ->to('to@example.com')
            ->text($emailMessageText);

        $this->decoratedTransport->expects(self::once())
            ->method('send')
            ->with($message)
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                sprintf('Failed to send a %s message: %s', get_debug_type($message), $exception->getMessage()),
                [
                    'message' => $message,
                    'exception' => $exception,
                    'sender' => 'from@example.com',
                    'recipients' => ['to@example.com'],
                ]
            );

        $this->transport->send($message);
    }

    public function testSendShouldLogWhenEnvelopeAndException(): void
    {
        $exception = new \RuntimeException('Sample exception message');

        $this->expectExceptionObject($exception);

        $message = new RawMessage('sample body');
        $envelope = new Envelope(new SymfonyAddress('from@example.com'), [new SymfonyAddress('to@example.com')]);

        $this->decoratedTransport->expects(self::once())
            ->method('send')
            ->with($message, $envelope)
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                sprintf('Failed to send a %s message: %s', get_debug_type($message), $exception->getMessage()),
                [
                    'message' => $message,
                    'exception' => $exception,
                    'sender' => 'from@example.com',
                    'recipients' => ['to@example.com'],
                ]
            );

        $this->transport->send($message, $envelope);
    }
}
